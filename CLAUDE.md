# PulseTurf

Med spa competitive intelligence SaaS built with Laravel 12 + Livewire 3.

## Project Status

- **Phase 0: Project Setup** — ✅ Complete (pushed to GitHub)
- **Phase 1: Database & Models** — ✅ Complete (pushed to GitHub)
- **Phase 2: Scraping Service** — ✅ Complete (pushed to GitHub)
- **Phase 3: Auth & Onboarding** — ✅ Complete
- **Phase 4: Digest Generation** — Not started
- **Phase 5: Stripe Billing** — Not started
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

## Conventions

- Standard Laravel 12 directory structure
- Migrations use sequential timestamps (2026_03_03_HHMMSS format)
- Cashier's published migrations handle `trial_ends_at` and Stripe columns on users
- Design docs live in `docs/plans/`
