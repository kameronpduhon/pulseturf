# PulseTurf

Med spa competitive intelligence SaaS built with Laravel 12 + Livewire 4.

## Project Status

- **Phase 0: Project Setup** — ✅ Complete (pushed to GitHub)
- **Phase 1: Database & Models** — ✅ Complete (pushed to GitHub)
- **Phase 2: Scraping Service** — Not started
- **Phase 3: Auth & Onboarding** — Not started
- **Phase 4: Digest Generation** — Not started
- **Phase 5: Stripe Billing** — Not started
- **Phase 6: Landing Page & Polish** — Not started
- **Phase 7: Deploy & Launch Prep** — Not started

## Tech Stack

- **Backend**: Laravel 12.53.0, PHP 8.4.18 (Herd)
- **Frontend**: Livewire 4.2.1, Vite, Tailwind CSS
- **Database**: MySQL 9.6.0 — db: `pulseturf`, user: `root`, no password
- **Billing**: Laravel Cashier (Stripe)
- **Email**: Resend
- **APIs**: Outscraper (reviews scraping), OpenAI (digest generation)

## Key Architecture

- **Models**: User, Business, Competitor, Review, ScrapeLog, Digest
- **Shared trait**: `App\Models\Concerns\HasGoogleBusinessData` (Business & Competitor)
- **Polymorphic**: Reviews (`reviewable`) and ScrapeLogs (`scrapeable`) for Business & Competitor
- **Demo seed**: `demo@pulseturf.com` / `password`

## Conventions

- Standard Laravel 12 directory structure
- Migrations use sequential timestamps (2026_03_03_HHMMSS format)
- Cashier's published migrations handle `trial_ends_at` and Stripe columns on users
- Design docs live in `docs/plans/`
