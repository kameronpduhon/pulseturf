# Phase 3: Auth & Onboarding — Design Doc

**Date:** 2026-03-03
**Status:** Approved

---

## Overview

Phase 3 adds user authentication (via Laravel Breeze + Livewire) and a multi-step onboarding wizard that guides new users through business setup, competitor selection, and initial data scraping.

## Key Decisions

- **Auth scaffolding:** Laravel Breeze with Livewire stack
- **Trial experience:** All trial users get up to 3 competitors (Pro-level). Starter plan downgrades to 1 competitor at billing time (Phase 5).
- **Email verification:** Required before accessing `/setup`
- **Business lookup failure:** Retry with different terms first, then Google Maps URL fallback after 2 failures
- **Post-setup experience:** Simple confirmation page — no dashboard
- **Wizard structure:** Single `SetupWizard` Livewire component with internal step state

---

## Section 1: Auth Scaffolding

Install Laravel Breeze with the Livewire stack. This provides login, register, password reset, email verification, and profile management out of the box.

**Customizations to Breeze defaults:**

1. **Registration form** — add a hidden `timezone` field, populated by JavaScript (`Intl.DateTimeFormat().resolvedOptions().timeZone`). Fallback: `America/Chicago`.

2. **User creation** — set `trial_ends_at = now()->addDays(14)` on new user creation.

3. **Post-registration redirect** — after email verification, redirect to `/setup` instead of `/dashboard`. Add middleware to check if user `hasActiveBusiness()` — if not, force redirect to `/setup`.

4. **Post-login redirect** — if user has completed setup, redirect to `/home`. If not, redirect to `/setup`.

---

## Section 2: Setup Wizard Component

**Route:** `GET /setup` — protected by `auth`, `verified`, and custom `EnsureSetupComplete` middleware (inverted — only accessible when setup is NOT complete).

**Component:** `App\Livewire\SetupWizard` — single component, 4 steps.

### Step 1: Your Business

- Text inputs: business name, full address (street, city, state, zip)
- "Find My Business" button → calls `OutscraperService::searchBusiness()` synchronously
- **On success:** Confirmation card with name, address, rating, review count
  - "Yes, this is my business" → saves to `businesses` table with `status = 'pending_setup'`
  - "Not my business, try again" → clears for re-entry
- **On failure:** Error message, retry. After 2 failed attempts, show Google Maps URL fallback input. Parse place ID from URL, call `OutscraperService::getBusinessInfo()` to verify.
- **Loading state:** `wire:loading` on the find button

### Step 2: Your Competitors

- Same find/confirm flow, repeated per competitor
- Start with 1 competitor form. After confirming, show "Add another competitor" button (up to 3)
- Minimum 1 competitor required to proceed
- Each confirmed competitor saved to `competitors` table

### Step 3: Scraping In Progress

- Dispatch `ScrapeBusinessJob` + `ScrapeCompetitorJob` for each entity
- Loading animation: "Setting up your first intelligence briefing..."
- `wire:poll.3s` checks scrape log statuses
- Individual progress indicators per entity
- Failed scrapes don't block — show a note, weekly job will retry

### Step 4: You're All Set

- Update business `status` to `'active'`
- Summary: business rating/reviews, number of competitors tracked
- "Your first briefing arrives Monday at 7 AM"
- Dispatch welcome email in background

---

## Section 3: Middleware & Route Structure

### New Middleware: `EnsureSetupComplete`

- Checks if authenticated user has an active business (`hasActiveBusiness()`)
- No active business → redirect to `/setup`
- Applied to all post-auth routes except `/setup` and `/logout`

### Route Groups

```
# Public
GET /                       → welcome page

# Auth (Breeze defaults in routes/auth.php)
GET|POST /login
POST /logout
GET|POST /register
GET|POST /forgot-password
GET|POST /reset-password/{token}
GET /verify-email
GET /verify-email/{id}/{hash}

# Authenticated + Verified (no setup required)
GET /setup                  → SetupWizard Livewire component

# Authenticated + Verified + Setup Complete
GET /home                   → simple status page
GET /profile                → Breeze profile page (name, email, password, timezone)
```

### Post-Login Routing Logic

1. No verified email → `/verify-email`
2. No active business → `/setup`
3. Otherwise → `/home`

### The `/home` Page

Intentionally minimal — status card showing what we're monitoring, when the next digest sends, and links to profile settings and (later) billing. Not a dashboard.

---

## Section 4: Error Handling & Edge Cases

### Outscraper Calls During Setup

- Synchronous (not queued) in Steps 1 & 2 for immediate feedback
- 15-second timeout per call. On timeout: "Search is taking longer than expected. Please try again."
- Rate limiting: unlikely at onboarding volume; if hit, show "Please wait a moment" message

### Setup Abandonment

- User can close browser and return — state persists in DB
- Business created but no competitors → resume at Step 2
- Competitors added but scraping not run → resume at Step 3

### Re-Running Setup

- Once `business.status = 'active'`, `/setup` redirects to `/home`
- Editing business/competitors is a Phase 6 concern (settings page)

### Scrape Failures During Onboarding

- Business or competitor scrape fails: proceed, show note, weekly job retries
- Only blocking failure: can't find business at all in Step 1 (handled by retry + URL fallback)

### Welcome Email

- Dispatched after Step 4 (business is active)
- Queued notification via Resend
- Contains: business name, competitor names, rating snapshot, next digest date
- Send failure is non-blocking

---

## Section 5: Testing Strategy

### Feature Tests (HTTP-level)

- Registration sets `trial_ends_at` to 14 days from now
- Registration stores browser-detected timezone
- Unverified users redirected to verification page
- Verified users without business redirected to `/setup`
- Users with active business redirected to `/home` from `/setup`
- `EnsureSetupComplete` middleware redirects correctly

### Livewire Component Tests (SetupWizard)

- Step 1: Find business calls OutscraperService (mocked), shows confirmation card
- Step 1: Failed lookup shows error, retry, URL fallback after 2 failures
- Step 1: Confirm creates `businesses` record with `status = 'pending_setup'`
- Step 2: Find/confirm works for 1-3 competitors, minimum 1 required
- Step 3: Dispatches scrape jobs, polls, transitions to Step 4
- Step 4: Sets business to `'active'`, dispatches welcome email

### Unit Tests

- `User::isOnTrial()` correctness
- `User::hasActiveBusiness()` checks active business status

### Mocking

- Outscraper calls: `Http::fake()` with existing Phase 2 test fixtures
- Scrape jobs: `Bus::fake()` / `Queue::fake()` to assert dispatch
