# Phase 4: Digest Generation — Design Doc

**Date:** 2026-03-03
**Status:** Approved
**Phase:** 4 of 7

---

## Overview

Phase 4 implements the core product: automated weekly email digests that synthesize Google review data into actionable competitive intelligence for med spa owners. The system scrapes fresh data every Sunday night, generates AI-powered digests via GPT-4o-mini, and delivers them Monday at 7 AM in each user's local timezone.

## Decisions

| Decision | Choice | Rationale |
|---|---|---|
| LLM model | GPT-4o-mini | ~$0.03/digest. Good quality/cost balance for structured summaries. |
| Sentiment analysis | During digest generation | Bundled into the digest prompt. Avoids per-review API calls. Simpler, cheaper. |
| Pipeline orchestration | Chained jobs via `Bus::chain()` | Scrape → Generate → Send as a linear chain per user. Built-in Laravel. |
| Email format | Laravel Markdown mail | Responsive out of the box. Built-in components (panel, table, button). Easy to maintain. |
| Feedback mechanism | Signed URL endpoint | Records thumbs up/down on digest record. No auth needed. Minimal effort, real data. |
| Architecture | Single service + chained jobs | Matches existing codebase patterns (OutscraperService, ScrapeBusinessJob). |
| Dev tooling | Manual Artisan command | `digest:generate {userId}` for testing with real data. |

---

## Architecture

### Component Overview

```
Sunday midnight UTC (scheduled)
    │
    ▼
digest:weekly command
    │
    ├── User 1: Bus::chain([ScrapeBusinessJob, ScrapeCompetitorJob×N, GenerateDigestJob, SendDigestJob])
    ├── User 2: Bus::chain([...])
    └── User N: Bus::chain([...])
```

### New Files

| File | Type | Purpose |
|---|---|---|
| `app/Services/DigestGeneratorService.php` | Service | Data gathering, prompt building, OpenAI call, response parsing |
| `app/Jobs/GenerateDigestJob.php` | Job | Calls service, creates Digest record, renders email HTML |
| `app/Jobs/SendDigestJob.php` | Job | Sends email via Resend, updates status |
| `app/Mail/DigestMail.php` | Mailable | Laravel Markdown mailable |
| `resources/views/emails/digest.blade.php` | Template | Markdown email template with digest sections |
| `app/Console/Commands/WeeklyDigestCommand.php` | Command | `digest:weekly` — scheduled pipeline orchestrator |
| `app/Console/Commands/GenerateDigestCommand.php` | Command | `digest:generate {userId}` — manual trigger for testing |
| `app/Http/Controllers/DigestFeedbackController.php` | Controller | Signed URL endpoint for thumbs up/down |
| `database/migrations/..._add_feedback_to_digests_table.php` | Migration | Adds `feedback` column to digests |
| `tests/Fixtures/openai/*.json` | Fixtures | Mock OpenAI chat completion responses |

### Modified Files

| File | Change |
|---|---|
| `routes/console.php` | Add weekly schedule |
| `routes/web.php` | Add feedback route |

---

## DigestGeneratorService

**Location:** `app/Services/DigestGeneratorService.php`

### Responsibilities

1. **Data gathering** — Load business + competitors with reviews from the last 7 days. Load previous digest for week-over-week comparison (rating deltas, review count changes).
2. **Prompt building** — Construct system prompt (tone, format, section structure) and user prompt (structured data payload).
3. **OpenAI call** — HTTP request to `chat/completions` endpoint via `Http::withHeaders()`. Model: `gpt-4o-mini`. Max tokens: ~1500.
4. **Response parsing** — Extract subject line and content sections from the LLM response.
5. **Return value** — Returns a data object with: subject line, content body, token usage, cost estimate.

### Key Method

```php
public function generate(Business $business): DigestResult
```

### Data Gathered

For the user's business:
- Current rating + review count
- Previous week's rating + review count (from last digest or scrape log)
- New reviews (last 7 days): author, rating, text, published_at
- Review sentiment summary (positive/negative/neutral counts)

For each competitor:
- Same data points as above
- Notable negative reviews (opportunity signals)

### Prompt Design

**System prompt** sets:
- Persona: "Smart, friendly marketing analyst writing for a med spa owner"
- Output format: structured sections with specific headers
- Tone: warm, direct, occasionally witty. Plain English, no jargon.
- Length: ~500-800 words total
- Instruction to highlight opportunities from competitor weaknesses
- Business category context (med spa/aesthetic clinic)

**User prompt** contains:
- Structured JSON-like data block with all business/competitor/review data
- Week-over-week deltas pre-calculated
- Instructions for the subject line format

### Data-Only Fallback

If OpenAI fails after retries, generate a template-based digest with just numbers and review snippets — no AI commentary. This ensures users always get something on Monday.

### OpenAI Integration

Same HTTP pattern as OutscraperService:
```php
Http::withHeaders(['Authorization' => 'Bearer ' . $this->apiKey])
    ->post('https://api.openai.com/v1/chat/completions', [...])
```

No SDK dependency. Config key from `config('services.openai.key')`.

---

## Jobs

### GenerateDigestJob

- **Input:** `User $user`
- **Process:**
  1. Call `DigestGeneratorService::generate($user->business)`
  2. Render the Markdown mail template to HTML
  3. Create `Digest` record: status `draft` → `generated`, store LLM output/prompt/model/tokens/cost
- **Retries:** 2, backoff `[60, 300]`
- **Failure:** Generate data-only fallback digest. Never leave a user without a digest.
- **Idempotency:** Check for existing digest with same `business_id` + `week_start` before generating.

### SendDigestJob

- **Input:** `Digest $digest`
- **Process:**
  1. Send email via `Mail::to($digest->user)->send(new DigestMail($digest))`
  2. Update digest: status → `sent`, `sent_at` → now
- **Delay:** Calculated to fire at Monday 7:00 AM in the user's timezone
- **Failure:** Mark digest status as `failed`. Log error.

### Delay Calculation

```php
$sendAt = Carbon::parse($digest->week_start)  // Monday
    ->setTimezone($user->timezone)
    ->setTime(7, 0, 0)                         // 7 AM local
    ->utc();                                     // Convert to UTC for queue
```

---

## Email Template

### Mailable: DigestMail

- **Class:** `app/Mail/DigestMail.php`
- **Subject:** `"Your Weekly Pulse: {Business Name} — Week of {Date}"`
- **From:** `digest@pulseturf.com`
- **Template:** `resources/views/emails/digest.blade.php` (Markdown)

### Template Sections (per PRD)

1. **Your Snapshot** — Rating + change arrow, review count + new this week, one-sentence sentiment summary
2. **Review Highlights** — Top positive snippet, flagged negative with response suggestion, common themes
3. **Competitor Watch** — Per competitor: name, rating + change, new review count, notable snippets, opportunity flags
4. **This Week's Insight** — One actionable recommendation from the AI
5. **Quick Numbers** — Text comparison table with arrows
6. **Footer** — Feedback links (👍/👎), manage preferences link, unsubscribe, PulseTurf branding

### Plain Text

Auto-generated by Laravel's Markdown mail system.

---

## Feedback Endpoint

### Route

```
GET /digest/{digest}/feedback/{type}
```

- Signed URL via `URL::signedRoute()` — no auth required
- `{type}` is `positive` or `negative`
- Validates signature, stores feedback, shows a simple "Thanks!" response

### Migration

Add to `digests` table:
```php
$table->string('feedback', 20)->nullable();  // 'positive' or 'negative'
```

### Controller

`DigestFeedbackController` — single `__invoke` method. Validates signed URL, validates type, updates digest record, returns a minimal Blade view with "Thanks for your feedback!"

---

## Artisan Commands

### digest:weekly

**Signature:** `digest:weekly`
**Schedule:** Sunday at midnight UTC (in `routes/console.php`)

**Logic:**
1. Query eligible users: have an active business AND (on trial OR subscribed)
2. Skip users who already have a digest for this `week_start` (Monday's date)
3. For each eligible user, dispatch `Bus::chain()`:
   - `ScrapeBusinessJob($business)`
   - `ScrapeCompetitorJob($competitor)` for each competitor
   - `GenerateDigestJob($user)`
   - `SendDigestJob($digest)` — delayed to Monday 7 AM local

### digest:generate

**Signature:** `digest:generate {userId} {--send}`
**Purpose:** Manual trigger for testing and debugging

**Logic:**
1. Load user, validate they have an active business
2. Optionally scrape first (fresh data), or use existing data
3. Call `DigestGeneratorService::generate()`
4. Create digest record
5. With `--send`: send email immediately (no delay)
6. Output digest summary to console

---

## Error Handling

| Scenario | Handling |
|---|---|
| OpenAI API error (5xx) | `GenerateDigestJob` retries 2x with backoff [60, 300]s. On final failure, generate data-only fallback. |
| OpenAI API error (4xx) | Fail fast (bad request, auth issue). Log error. Generate data-only fallback. |
| LLM output too short/malformed | Validate output has expected section markers. If fails, retry once with adjusted prompt. Then data-only fallback. |
| Scrape fails for one profile | Chain continues. Digest uses last known data. Includes note: "We couldn't pull fresh data for [name] this week." |
| All scrapes fail for a user | Skip digest generation entirely. Log the failure. |
| Token budget exceeded | `max_tokens` capped in API call. If truncated, data-only fallback. |
| Email send fails | `SendDigestJob` marks digest as `failed`. Can be retried manually via `digest:generate --send`. |
| Duplicate digest | Idempotency check on `business_id` + `week_start` unique constraint. Skip if exists. |

---

## Testing Strategy

### Unit Tests

- **DigestGeneratorService**: Mock OpenAI HTTP responses via `Http::fake()`. Test:
  - Correct prompt structure with business/competitor data
  - Response parsing into subject line + sections
  - Data-only fallback when OpenAI fails
  - Handling of users with no new reviews
  - Week-over-week delta calculations

- **GenerateDigestJob**: Mock service. Test:
  - Digest record creation with correct fields
  - Status transitions (draft → generated)
  - Idempotency (skip if digest exists for week)
  - Fallback on service failure

- **SendDigestJob**: Mock Mail facade. Test:
  - Email sent to correct user
  - Status updated to `sent` with `sent_at`
  - Delay calculation for different timezones

- **DigestMail**: Test rendered content contains expected sections.

### Feature Tests

- **Feedback endpoint**: Signed URL works, stores feedback, rejects unsigned URLs, rejects invalid types
- **WeeklyDigestCommand**: Correct user selection (active + trial/subscribed), skips users with existing digest, dispatches correct chain
- **GenerateDigestCommand**: Generates for specified user, `--send` flag triggers email

### Test Fixtures

`tests/Fixtures/openai/chat-completion.json` — Sample GPT-4o-mini response with digest content.
`tests/Fixtures/openai/chat-completion-error.json` — Error response for fallback testing.

---

## Summary of Changes

**New files:** ~10 (1 service, 2 jobs, 1 mailable, 1 email template, 2 commands, 1 controller, 1 migration, test fixtures)
**Modified files:** 2 (routes/console.php, routes/web.php)
**New tests:** Estimated ~40-50 assertions covering service, jobs, commands, feedback, and email rendering
