# Phase 7: Deploy & Launch Prep — Design Document

**Date**: 2026-03-04
**Status**: Draft

## Overview

Phase 7 takes PulseTurf from local development to a live production environment. Infrastructure is Ploi + Hetzner (replacing the originally planned Forge + DigitalOcean for better cost/performance). The phase covers server provisioning, domain/SSL, production environment configuration, Stripe live mode, end-to-end validation with real med spas, and soft launch.

## Decisions

- **Server management**: Ploi (~$8/mo) — Laravel-native, supports queue workers, scheduler, SSL, deployments
- **VPS provider**: Hetzner CX22 (~$4.50/mo) — 2 vCPU, 4GB RAM, 40GB SSD, Ashburn VA datacenter
- **Total infrastructure cost**: ~$12.50/mo (vs ~$18/mo for Forge + DigitalOcean)
- **Database**: MySQL 8 on the same server (no managed DB needed at this scale)
- **Domain**: pulseturf.com (or alternative if unavailable)
- **SSL**: Let's Encrypt via Ploi (auto-renewal)
- **Deployment**: Git push to main → Ploi auto-deploy
- **Email**: Resend (already configured, just swap to production API key)

## Infrastructure Architecture

```
┌─────────────────────────────────────────┐
│  Hetzner CX22 (Ashburn, VA)            │
│  Ubuntu 24.04 LTS                       │
│                                         │
│  ┌─────────────┐  ┌──────────────────┐  │
│  │  Nginx      │  │  PHP 8.4 (FPM)   │  │
│  │  :80/:443   │  │  Laravel 12       │  │
│  └─────────────┘  └──────────────────┘  │
│                                         │
│  ┌─────────────┐  ┌──────────────────┐  │
│  │  MySQL 8    │  │  Supervisor      │  │
│  │  :3306      │  │  Queue Worker    │  │
│  └─────────────┘  └──────────────────┘  │
│                                         │
│  ┌─────────────┐  ┌──────────────────┐  │
│  │  Cron       │  │  Let's Encrypt   │  │
│  │  Scheduler  │  │  SSL             │  │
│  └─────────────┘  └──────────────────┘  │
└─────────────────────────────────────────┘

External Services:
  - Stripe (billing)
  - Resend (email)
  - Outscraper (Google reviews)
  - OpenAI (digest generation)
```

## Step-by-Step Implementation

### Step 1: Provision Hetzner Server

1. Create Hetzner account + CX22 server in Ashburn, VA
2. Select Ubuntu 24.04 LTS
3. Add SSH key for Ploi access

### Step 2: Connect Ploi + Provision Server

1. Create Ploi account
2. Connect Hetzner API credentials to Ploi
3. Provision server via Ploi (installs Nginx, PHP 8.4, MySQL 8, Composer, Node, Supervisor)
4. Create site in Ploi pointing to the repo

### Step 3: Configure Domain & SSL

1. Purchase/configure domain (pulseturf.com or alternative)
2. Point DNS A record to Hetzner server IP
3. Enable Let's Encrypt SSL in Ploi (auto-provisioned once DNS propagates)

### Step 4: Configure Production Environment

Production `.env` values to set in Ploi:

```env
APP_NAME=PulseTurf
APP_ENV=production
APP_DEBUG=false
APP_URL=https://pulseturf.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=pulseturf
DB_USERNAME=pulseturf
DB_PASSWORD=<generated>

MAIL_MAILER=resend
RESEND_API_KEY=<production-key>
MAIL_FROM_ADDRESS=digest@pulseturf.com
MAIL_FROM_NAME=PulseTurf

STRIPE_KEY=<live-publishable-key>
STRIPE_SECRET=<live-secret-key>
STRIPE_WEBHOOK_SECRET=<live-webhook-secret>
CASHIER_CURRENCY=usd

OUTSCRAPER_API_KEY=<production-key>
OPENAI_API_KEY=<production-key>

SESSION_DRIVER=database
QUEUE_CONNECTION=database
CACHE_STORE=file
```

### Step 5: Set Up Deployment Script

Ploi deploy script (runs on each `git push` to main):

```bash
cd /home/ploi/pulseturf.com
git pull origin main
composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan queue:restart
```

### Step 6: Set Up Queue Worker

Configure in Ploi's Supervisor/Daemon section:

- **Command**: `php artisan queue:work --sleep=3 --tries=3 --max-time=3600`
- **Processes**: 1 (sufficient for launch scale)
- **Auto-restart**: enabled

Jobs that depend on this:
- `ScrapeBusinessJob` / `ScrapeCompetitorJob`
- `GenerateDigestJob` / `SendDigestJob`
- `WelcomeNotification` and all billing notifications

### Step 7: Set Up Cron (Scheduler)

Ploi adds the Laravel scheduler cron automatically, but verify:

```cron
* * * * * cd /home/ploi/pulseturf.com && php artisan schedule:run >> /dev/null 2>&1
```

Scheduled commands:
- `digest:weekly` — Sundays 00:00 UTC
- `trial:reminders` — Daily 09:00

### Step 8: Configure Stripe Live Mode

1. Switch Stripe dashboard from test to live mode
2. Create live products + prices matching test config:
   - `price_starter_monthly` — $29/mo
   - `price_starter_annual` — $290/yr
   - `price_pro_monthly` — $79/mo
   - `price_pro_annual` — $790/yr
3. Create live webhook endpoint: `https://pulseturf.com/stripe/webhook`
   - Events: `invoice.payment_failed`, `customer.subscription.deleted`
4. Update `.env` with live Stripe keys + webhook secret
5. Verify webhook receives test ping from Stripe dashboard

### Step 9: Database Migration & Seed

```bash
php artisan migrate --force
```

No production seeding needed — users will register organically.

### Step 10: End-to-End Validation

Create 5 test accounts with real med spas in different cities:

| # | Med Spa Market | Purpose |
|---|---|---|
| 1 | Miami, FL | High competition market |
| 2 | Scottsdale, AZ | Dense med spa cluster |
| 3 | Nashville, TN | Mid-size market |
| 4 | Austin, TX | Growing market |
| 5 | Denver, CO | Different timezone |

For each test account:
1. Register with a real email address
2. Complete setup wizard (find real business + 1 competitor)
3. Verify scrape jobs complete successfully
4. Manually trigger digest: `php artisan digest:generate {userId} --send`
5. Verify digest email arrives with real data
6. Test billing flow: subscribe to Starter plan with Stripe test card
7. Verify subscription shows correctly in settings

### Step 11: Production Checklist

Before soft launch, verify:

- [ ] `APP_DEBUG=false` in production
- [ ] SSL working (HTTPS redirect)
- [ ] Landing page loads correctly
- [ ] Registration + onboarding flow works
- [ ] Scraping jobs complete without errors
- [ ] Digest generation + email delivery works
- [ ] Stripe billing (subscribe, cancel, resume) works
- [ ] Webhook endpoint receiving events
- [ ] Queue worker processing jobs
- [ ] Scheduler running (check `storage/logs/laravel.log`)
- [ ] Error logging working (check `storage/logs/`)
- [ ] `MAIL_FROM_ADDRESS` verified in Resend
- [ ] DNS fully propagated

### Step 12: Soft Launch

1. Manually sign up 5–10 beta users from cold outreach (med spa owners)
2. Monitor logs for errors during first week
3. Verify first automatic weekly digest cycle (Sunday scrape → Monday email)
4. Collect feedback from beta users

## Cost Summary

| Service | Monthly Cost |
|---|---|
| Hetzner CX22 | ~$4.50 |
| Ploi | ~$8.00 |
| Domain | ~$1.00 (amortized) |
| Resend | Free tier (100 emails/day) |
| Outscraper | Pay-per-use (~$5–10 at launch scale) |
| OpenAI | Pay-per-use (~$1–2 at launch scale) |
| **Total** | **~$20/mo at launch** |

## Rollback Strategy

- Ploi supports instant rollback to previous deployment
- Database: take a MySQL dump before each migration (`mysqldump` via Ploi scheduled backup)
- If critical failure: revert deploy in Ploi, restore DB from backup
