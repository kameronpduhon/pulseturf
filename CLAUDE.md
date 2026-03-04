# PulseTurf

Med spa competitive intelligence SaaS built with Laravel 12 + Livewire 3.

## Project Status

- **Phase 0: Project Setup** — ✅ Complete (pushed to GitHub)
- **Phase 1: Database & Models** — ✅ Complete (pushed to GitHub)
- **Phase 2: Scraping Service** — ✅ Complete (pushed to GitHub)
- **Phase 3: Auth & Onboarding** — ✅ Complete (pushed to GitHub)
- **Phase 4: Digest Generation** — ✅ Complete (pushed to GitHub)
- **Phase 5: Stripe Billing** — ✅ Complete (pushed to GitHub)
- **Phase 6: Landing Page & Polish** — Not started
- **Phase 7: Deploy & Launch Prep** — Not started

## Tech Stack

- **Backend**: Laravel 12.53.0, PHP 8.4.18 (Herd)
- **Frontend**: Livewire 3.7.11 (downgraded by Breeze), Livewire Volt 1.10.3, Vite, Tailwind CSS
- **Database**: MySQL 9.6.0 — db: `pulseturf`, user: `root`, no password
- **Billing**: Laravel Cashier (Stripe)
- **Email**: Resend
- **APIs**: Outscraper (reviews scraping), OpenAI (digest generation)

## Key Architecture

- **Models**: User, Business, Competitor, Review, ScrapeLog, Digest
- **Shared trait**: `App\Models\Concerns\HasGoogleBusinessData` (Business & Competitor)
- **Polymorphic**: Reviews (`reviewable`) and ScrapeLogs (`scrapeable`) for Business & Competitor
- **Demo seed**: `demo@pulseturf.com` / `password`

## Scraping Service (Phase 2)

- **OutscraperService** (`app/Services/OutscraperService.php`): HTTP client for Outscraper API
  - `searchBusiness(name, address)`, `getReviews(placeId)`, `getBusinessInfo(placeId)`
  - Maps Outscraper fields to our model fields (e.g. `autor_name` → `author_name`, `site` → `website`)
- **OutscraperException** (`app/Exceptions/OutscraperException.php`): Static factories `apiError()`, `noResults()`, `missingApiKey()`
- **ScrapeBusinessJob** / **ScrapeCompetitorJob** (`app/Jobs/`): Queued jobs with 3 retries, exponential backoff, 4xx fail-fast
- **Test fixtures**: `tests/Fixtures/outscraper/*.json` (5 files)

## Auth & Onboarding (Phase 3)

- **Breeze stack**: Livewire + Volt functional components (`resources/views/livewire/pages/auth/`)
- **Register** (`register.blade.php`): `timezone` via `@this.set()`, `trial_ends_at = now()->addDays(14)`, redirects to `/setup`
- **Login** (`login.blade.php`): redirects to `/home` if `hasActiveBusiness()`, else `/setup`
- **Middleware**: `EnsureSetupComplete` (`app/Http/Middleware/`) — alias `setup.complete`, null-safe
- **SetupWizard** (`app/Livewire/SetupWizard.php`): 4-step onboarding wizard
  - Step 1: Find business via OutscraperService (with Place ID fallback after 2 failures)
  - Step 2: Add 1–3 competitors (same flow, IDOR-protected removal, backend limit enforcement)
  - Step 3: Dispatch scrape jobs, poll progress via `wire:poll.3s`, eager-loaded status checks
  - Step 4: Activate business, queue WelcomeNotification, show summary
  - Supports session resumption (pending business → resume at correct step)
- **WelcomeNotification** (`app/Notifications/`): ShouldQueue mail notification
- **Home page** (`resources/views/home.blade.php`): minimal status card with next digest date
- **Routes**: `/setup` (auth+verified), `/home` (auth+verified+setup.complete), `/dashboard` → `/home` redirect

## Digest Generation (Phase 4)

- **DigestGeneratorService** (`app/Services/DigestGeneratorService.php`): OpenAI GPT-4o-mini integration
  - `generate(Business $business): DigestResult` — gathers data, builds prompts, calls OpenAI, parses JSON response
  - Automatic fallback to template-based digest on any `OpenAIException`
  - HTML sanitization via `strip_tags()` with allowlist on AI output
  - `gatherData()` loads business + competitors with 7-day reviews, computes week-over-week deltas
- **DigestResult** (`app/Services/DigestResult.php`): Readonly DTO (subjectLine, content, prompt, rawResponse, model, tokensUsed, costCents, isFallback)
- **OpenAIException** (`app/Exceptions/OpenAIException.php`): Static factories `apiError()`, `missingApiKey()`, `rateLimited()`, `malformedResponse()`
- **GenerateDigestJob** (`app/Jobs/`): 2 retries, creates Digest record, dispatches SendDigestJob delayed to Monday 7 AM user timezone
- **SendDigestJob** (`app/Jobs/`): 3 retries, atomic idempotency guard (`UPDATE WHERE status != 'sent'`), sends DigestMail
- **DigestMail** (`app/Mail/DigestMail.php`): Markdown mailable with signed feedback URLs
- **WeeklyDigestCommand** (`digest:weekly`): Scheduled Sundays 00:00 UTC, chains ScrapeBusinessJob → ScrapeCompetitorJob×N → GenerateDigestJob per eligible user
- **GenerateDigestCommand** (`digest:generate {userId} {--send}`): Manual trigger with `updateOrCreate` idempotency
- **DigestFeedbackController** (`app/Http/Controllers/`): Invokable, signed URL, positive/negative feedback
- **Route**: `GET /digest/{digest}/feedback/{type}` (public, signed middleware)
- **Email template**: `resources/views/emails/digest.blade.php` (Markdown with feedback buttons)
- **Feedback page**: `resources/views/feedback/thanks.blade.php` (standalone HTML)
- **Test fixtures**: `tests/Fixtures/openai/*.json` (2 files)

## Stripe Billing (Phase 5)

- **EnsureSubscribed middleware** (`app/Http/Middleware/`): alias `subscribed`, gates `/home` and `/profile`; allows trial OR subscribed users through
- **BillingPage** (`app/Livewire/BillingPage.php`): Full Livewire component with Stripe Elements integration
  - `mount()`: creates SetupIntent once (stored as `$setupIntentClientSecret`), pre-selects current plan if subscribed
  - `subscribe(paymentMethodId)`: creates Cashier subscription, clears `trial_ends_at`, redirects to `/home`
  - `swapPlan(planKey)`: swaps subscription price via Cashier, server-side guard against no-op
  - `updatePaymentMethod(paymentMethodId)`: updates default payment method
  - `cancelSubscription()` / `resumeSubscription()`: cancel at period end / resume from grace period
  - Private helpers: `getPlans()` (4-plan array), `getPriceId()`, `getPlanKeyFromPriceId()`
- **Billing view** (`resources/views/livewire/billing-page.blade.php`): 3 states (trial, subscribed, expired)
  - `@assets` loads Stripe.js, `@script` handles `confirmCardSetup()` flow
  - `wire:ignore` on card elements prevents Livewire from destroying Stripe Elements
  - Cancel confirmation modal, grace period resume, past-due warning banner
- **StripeWebhookController** (`app/Http/Controllers/`): extends Cashier's WebhookController
  - `handleInvoicePaymentFailed`: sends PaymentFailedNotification
  - `handleCustomerSubscriptionDeleted`: calls parent first (syncs DB), sends SubscriptionCancelledNotification
- **TrialReminderCommand** (`trial:reminders`): scheduled daily at 09:00
  - Sends TrialEndingNotification (2 days), TrialLastDayNotification (1 day), TrialExpiredNotification (today)
  - Filters `whereDoesntHave('subscriptions')` to skip converted users, uses `cursor()` for memory efficiency
- **5 Notifications** (all ShouldQueue, Queueable, MailMessage builder pattern):
  - `TrialEndingNotification`, `TrialLastDayNotification`, `TrialExpiredNotification`
  - `PaymentFailedNotification`, `SubscriptionCancelledNotification`
- **User model updates**: `isSubscribedOrTrial()`, plan-aware `competitorLimit()` (Pro=3, Starter/trial=1)
- **Routes**: `/billing` (auth+verified+setup.complete, NOT subscribed-gated), `/stripe/webhook` (POST, CSRF-exempt)
- **CSRF exemption**: `stripe/*` in bootstrap/app.php; `Cashier::ignoreRoutes()` in AppServiceProvider
- **Navigation**: Billing link in desktop + mobile nav, trial countdown badge ("Trial: Xd left")

## Conventions

- Standard Laravel 12 directory structure
- Migrations use sequential timestamps (2026_03_03_HHMMSS format)
- Cashier's published migrations handle `trial_ends_at` and Stripe columns on users
- Design docs live in `docs/plans/`
