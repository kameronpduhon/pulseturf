# Styled Digest — Feature Plan

**Branch:** `feature/styled-digest`  
**Estimated time:** 4–5 hours  
**Goal:** Replace raw HTML digest output with structured JSON from GPT, rendered via a polished shared Blade template matching the landing page mockup.

---

## Overview

Change digest generation to return structured JSON with each section as a named key. Store it in a new `content_json` DB column. Render both the email and digest history page via a shared styled Blade partial.

---

## Step 1 — DB Migration

Create a new migration to add `content_json` column to `digests` table:
- `content_json` — `longText`, nullable (JSON string of structured sections)
- Keep `html_content` — used as fallback for old digests that don't have JSON yet

---

## Step 2 — Update GPT Prompt

Change `buildSystemPrompt()` in `DigestGeneratorService.php` to return this JSON structure:

```json
{
  "subject_line": "...",
  "performance_snapshot": {
    "rating": 4.8,
    "review_count": 142,
    "new_reviews": 6,
    "rating_change": "+0.1",
    "summary": "..."
  },
  "review_highlights": [
    { "author": "Jane D.", "rating": 5, "text": "...", "type": "positive" }
  ],
  "competitor_watch": [
    { "name": "Glo Med Spa", "rating": 4.6, "review_count": 98, "new_reviews": 3, "insight": "..." }
  ],
  "sentiment_trends": {
    "positive": 5,
    "neutral": 0,
    "negative": 1,
    "summary": "..."
  },
  "action_items": [
    "Respond to the 2-star review from Monday",
    "Share your top quote on Instagram"
  ],
  "week_ahead": "..."
}
```

---

## Step 3 — Update `parseResponse()` + `DigestResult`

- Parse the new JSON structure in `parseResponse()`
- Pass `content_json` through `DigestResult` → `GenerateDigestJob` → saved to DB
- Keep `html_content` populated too (rendered from the template) for email compatibility

---

## Step 4 — Update `Digest` Model

- Add `content_json` to `$fillable`
- Add a `sections()` helper that decodes the JSON and returns an array

---

## Step 5 — Create Shared Blade Partial

New file: `resources/views/partials/digest-card.blade.php`

Renders all 6 sections as styled cards matching the landing page mockup:
- 📊 **Performance Snapshot** — stat boxes with rating, review count, changes
- 💬 **Review Highlights** — quote cards with star rating and author
- 🔍 **Competitor Watch** — comparison rows with rating badges
- 📈 **Sentiment Trends** — positive/negative breakdown with visual bar
- ✅ **Action Items** — numbered checklist cards
- 🗓️ **Week Ahead** — motivational closing card

This partial is used in BOTH the email template AND the digest history page.

---

## Step 6 — Update Email Template

Replace `resources/views/emails/digest.blade.php`:
- Remove raw `{!! $digest->html_content !!}`
- Include `@include('partials.digest-card', ['sections' => $digest->sections()])`
- Keep the feedback buttons (👍/👎)
- Use inline CSS (email-safe, no Tailwind classes)

---

## Step 7 — Update Digest History Page

Update `resources/views/livewire/digest-history.blade.php`:
- Replace the `prose` div that dumps raw HTML
- Include `@include('partials.digest-card', ['sections' => $digest->sections()])`
- Old digests without `content_json` fall back to rendering `html_content` as before

---

## Step 8 — Tests + Deploy

- Run `php artisan test` — make sure existing tests still pass
- Manually trigger a test digest: `php artisan digest:generate --user=1`
- Verify email looks right + digest history looks right
- Push to feature branch → PR → merge → Deploy Now in Ploi

---

## Files Touched

| File | Change |
|---|---|
| `database/migrations/xxxx_add_content_json_to_digests.php` | New migration |
| `app/Models/Digest.php` | Add `content_json` to fillable + `sections()` helper |
| `app/Services/DigestGeneratorService.php` | New GPT prompt + parse structured JSON |
| `app/Services/DigestResult.php` | Add `contentJson` property |
| `app/Jobs/GenerateDigestJob.php` | Save `content_json` to DB |
| `resources/views/partials/digest-card.blade.php` | New styled shared partial (NEW FILE) |
| `resources/views/emails/digest.blade.php` | Use new partial |
| `resources/views/livewire/digest-history.blade.php` | Use new partial, fallback for old digests |
