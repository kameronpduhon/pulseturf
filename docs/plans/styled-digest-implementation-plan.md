# Styled Digest — Implementation Plan

## Context

Currently, digest generation asks GPT to return a flat `{ subject_line, body }` JSON where `body` is a raw HTML string. This HTML is stored in `html_content` and dumped directly into both the email template and digest history page with `{!! !!}`. The result is unstyled prose that doesn't match the polished look of the rest of the app.

This plan replaces that with **structured JSON** from GPT (one key per section), stored in a new `content_json` column, and rendered via **two styled Blade partials** — one for email (inline CSS) and one for web (Tailwind). Both AI-generated and fallback digests will produce structured JSON.

---

## Implementation Steps

### Step 1: Migration

**New file:** `database/migrations/2026_03_05_XXXXXX_add_content_json_to_digests_table.php`

- Add `content_json` as `longText`, nullable, after `html_content`
- Down: drop the column

---

### Step 2: Update DigestResult DTO

**File:** `app/Services/DigestResult.php`

- Add `public readonly ?array $contentJson` parameter (3rd position, after `content`, before `prompt`)
- Nullable because edge cases (e.g., malformed GPT response caught late) might not produce it

---

### Step 3: Update DigestGeneratorService

**File:** `app/Services/DigestGeneratorService.php`

#### 3a. Update `buildSystemPrompt()`
Replace the current prompt requesting `{ subject_line, body }` with one requesting the structured JSON schema:
```
subject_line, performance_snapshot, review_highlights, competitor_watch,
sentiment_trends, action_items, week_ahead
```
Include exact field names/types for each nested object in the prompt so GPT returns consistent structure.

#### 3b. Update `parseResponse()`
- Currently returns `[$subjectLine, $body]` — change to return `[$subjectLine, $sectionsArray]`
- Validate all 7 top-level keys exist
- Sanitize individual string fields with `strip_tags($value, '')` (no HTML allowed in text values — the Blade partial handles all markup)
- Throw `OpenAIException::malformedResponse()` if required keys are missing (triggers fallback)

#### 3c. Add `renderHtmlContent(array $sections): string`
New private method that renders the email partial server-side:
```php
return view('partials.digest-email', ['sections' => $sections])->render();
```
This produces `html_content` from the structured data rather than from GPT's raw HTML.

#### 3d. Update `generate()` flow
After parsing: call `renderHtmlContent($sections)` to get HTML, then construct `DigestResult` with both `content` (rendered HTML) and `contentJson` (sections array).

#### 3e. Refactor `generateFallback()`
Rewrite to build the same structured sections array from business data (same data it currently uses), then call `renderHtmlContent()` for `html_content`. Return `DigestResult` with both `content` and `contentJson` populated.

#### 3f. Increase `MAX_TOKENS` from 1500 to 2000
Structured JSON is more verbose than flat HTML.

---

### Step 4: Update Digest Model

**File:** `app/Models/Digest.php`

- Add `'content_json'` to `$fillable`
- Add cast: `'content_json' => 'array'` (auto encode/decode)
- Add `sections(): ?array` helper — returns `$this->content_json` (null for old digests)

---

### Step 5: Update GenerateDigestJob

**File:** `app/Jobs/GenerateDigestJob.php`

Add to `Digest::create()`:
```php
'content_json' => $result->contentJson, // cast handles encoding
```

---

### Step 6: Update GenerateDigestCommand

**File:** `app/Console/Commands/GenerateDigestCommand.php`

Add `'content_json' => $result->contentJson` to the `updateOrCreate` values array.

---

### Step 7: Create Email Blade Partial

**New file:** `resources/views/partials/digest-email.blade.php`

Accepts `$sections` array. Renders all 6 sections with **inline CSS only** (no Tailwind classes — email clients strip them). Design matches the landing page palette:

- **Colors:** indigo-600 (`#4f46e5`) accent, gray-700 (`#374151`) text, gray-100 (`#f3f4f6`) backgrounds, white cards
- **Layout:** Table-based for stat grids, simple divs for stacked content
- **Font:** System font stack (Arial/Helvetica fallback)

Sections:
1. **Performance Snapshot** — Horizontal stat boxes (rating, review count, new reviews, rating change with green/red), summary paragraph
2. **Review Highlights** — Quote blocks with left indigo border, author, star rating, review text
3. **Competitor Watch** — Rows with name, rating badge, review count, insight text
4. **Sentiment Trends** — Positive/neutral/negative counts with colored inline bar, summary
5. **Action Items** — Numbered list with indigo badges
6. **Week Ahead** — Styled closing card with subtle background

---

### Step 8: Create Web Blade Partial

**New file:** `resources/views/partials/digest-web.blade.php`

Accepts `$sections` array. Uses **Tailwind CSS** matching the app's design system:

- `rounded-xl`, `shadow-sm`, `border border-gray-100` cards
- `text-indigo-600` accents
- `flex`, `grid`, `gap-*` layouts
- Same 6 sections as the email partial, just with Tailwind markup instead of inline styles

No outer container — this renders inside the existing accordion on the digest history page.

---

### Step 9: Update Email Template

**File:** `resources/views/emails/digest.blade.php`

Replace `{!! $digest->html_content !!}` with:
```blade
@if ($digest->sections())
    @include('partials.digest-email', ['sections' => $digest->sections()])
@else
    {!! $digest->html_content !!}
@endif
```

Keep `<x-mail::message>` wrapper, heading, and feedback buttons unchanged.

---

### Step 10: Update Digest History Page

**File:** `resources/views/livewire/digest-history.blade.php`

Replace the prose div that dumps `html_content` with:
```blade
@if ($digest->sections())
    @include('partials.digest-web', ['sections' => $digest->sections()])
@else
    <div class="prose prose-sm max-w-none text-gray-700">
        {!! $digest->html_content !!}
    </div>
@endif
```

---

### Step 11: Update Tests

**File:** `tests/Feature/DigestPipelineTest.php`

#### 11a. Update fixture
**File:** `tests/Fixtures/openai/chat-completion.json`
Replace the `body` HTML string in the response content with the new structured JSON schema (all 7 keys).

#### 11b. Update existing test assertions
- Tests asserting `$result->content` contains `<h2>Performance Snapshot</h2>` — update to assert the text "Performance Snapshot" appears in rendered HTML (the partial wraps it differently)
- Add assertions for `$result->contentJson` being an array with expected keys
- Update fallback tests to also verify `contentJson` is populated
- Update sanitization test to verify script tags stripped from individual text fields

#### 11c. Add new tests
- `test_digest_sections_returns_decoded_array` — model with content_json
- `test_digest_sections_returns_null_without_content_json` — old digest
- `test_email_falls_back_for_old_digests` — digest without content_json renders html_content
- `test_generate_job_saves_content_json` — assert DB column populated

#### 11d. Update DigestFactory
**File:** `database/factories/DigestFactory.php`
- Update `generated()` state to include `content_json` with sample structured data
- Add `withoutContentJson()` state for backward-compat testing

---

## Implementation Order

1. Migration (Step 1)
2. DigestResult DTO (Step 2)
3. Email Blade partial (Step 7) — needed by service's `renderHtmlContent()`
4. DigestGeneratorService (Step 3) — depends on DTO + partial
5. Digest model (Step 4)
6. GenerateDigestJob + Command (Steps 5-6)
7. Web Blade partial (Step 8)
8. Email template update (Step 9)
9. Digest history update (Step 10)
10. Tests (Step 11)

---

## Files Touched

| File | Change |
|---|---|
| `database/migrations/2026_03_05_XXXXXX_add_content_json_to_digests.php` | New migration |
| `app/Services/DigestResult.php` | Add `contentJson` property |
| `app/Services/DigestGeneratorService.php` | New GPT prompt, parse structured JSON, renderHtmlContent(), refactor fallback |
| `app/Models/Digest.php` | Add `content_json` to fillable, cast, `sections()` helper |
| `app/Jobs/GenerateDigestJob.php` | Save `content_json` to DB |
| `app/Console/Commands/GenerateDigestCommand.php` | Save `content_json` to DB |
| `resources/views/partials/digest-email.blade.php` | **New** — styled email partial (inline CSS) |
| `resources/views/partials/digest-web.blade.php` | **New** — styled web partial (Tailwind) |
| `resources/views/emails/digest.blade.php` | Use email partial with fallback |
| `resources/views/livewire/digest-history.blade.php` | Use web partial with fallback |
| `tests/Fixtures/openai/chat-completion.json` | Updated fixture with structured JSON |
| `tests/Feature/DigestPipelineTest.php` | Updated + new test assertions |
| `database/factories/DigestFactory.php` | Updated `generated()` state + `withoutContentJson()` |

---

## Verification

1. `php artisan migrate` — confirm column added
2. `php artisan test` — all existing + new tests pass
3. `php artisan digest:generate 1` — generate a test digest, verify:
   - `content_json` column populated in DB
   - `html_content` column populated with styled HTML
4. Check digest history page — styled sections render correctly
5. Preview email via Mailpit/log — styled sections with inline CSS render correctly
6. Test backward compat — old digest without `content_json` still renders `html_content` in both email and history page
