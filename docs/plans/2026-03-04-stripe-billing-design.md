# Phase 5: Stripe Billing — Design Doc

**Date:** 2026-03-04
**Status:** Approved

---

## Overview

Add Stripe billing to PulseTurf with a custom Livewire billing page, hard trial gate, webhook handling, and trial expiration reminder emails.

## Decisions

- **Trial gate**: Hard gate — after trial expires, all app routes redirect to `/billing`
- **Billing UI**: Custom Livewire page with Stripe Elements (no Stripe Checkout redirect)
- **Plan changes**: Immediate swap with proration (upgrade unlocks instantly, downgrade also swaps immediately)
- **Early subscribe**: `/billing` accessible during trial so users can convert early
- **Trial emails**: Built in this phase (day 12, 13, 14 reminders)
- **Webhooks**: Extend Cashier's built-in webhook controller with custom overrides

## 1. Middleware & Access Control

**New middleware: `EnsureSubscribed`** (alias: `subscribed`)
- Checks `isOnTrial() || subscribed()` (Cashier method)
- If neither → redirect to `/billing`
- `past_due` subscriptions pass (Cashier's `subscribed()` returns true for past_due)

**Route structure:**
```
/billing     — auth + verified + setup.complete
/home        — auth + verified + setup.complete + subscribed
/profile     — auth + verified + setup.complete + subscribed
```

**Navigation**: "Billing" link in app nav. During trial, show badge: "Trial: X days left".

## 2. Billing Page UI

**Livewire component: `BillingPage`** — three states:

**State 1 — Trial user (no subscription)**
- Trial status banner with days remaining
- 4 plan cards: Starter Monthly ($29), Starter Annual ($290), Pro Monthly ($79), Pro Annual ($790)
- Annual cards show "Save ~17%" badge
- Stripe Elements card input
- "Subscribe" button → creates Stripe customer + subscription via Cashier

**State 2 — Active subscriber**
- Current plan display with next billing date and amount
- Plan change options (swap to different plan)
- Card on file (brand + last 4) with "Update Card" option
- "Cancel Subscription" with confirmation modal → cancels at period end

**State 3 — Expired trial (hard-gated)**
- Same as State 1 with messaging: "Your trial has ended. Subscribe to keep your Monday briefings."

**Stripe Elements flow:**
1. Load Stripe.js via CDN
2. Mount card element in Livewire view
3. On submit: JS creates payment method token → `$wire.call('subscribe', paymentMethodId)`
4. Server: `$user->newSubscription('default', $priceId)->create($paymentMethodId)`

**Plan switching:**
- All plan changes use `$user->subscription('default')->swap($newPriceId)`
- Cashier handles proration automatically

## 3. Webhook Handling & Subscription Lifecycle

**`StripeWebhookController`** extends Cashier's `WebhookController`:
- `handleInvoicePaymentFailed`: Send payment failure notification with link to `/billing`
- `handleCustomerSubscriptionDeleted`: Send cancellation confirmation email

**Route:** `POST /stripe/webhook` — CSRF exempted, Cashier verifies Stripe signature.

**Status effects:**

| Status | Digests | App Access |
|---|---|---|
| `active` | Yes | Full |
| `past_due` | Yes (grace) | Full + warning banner |
| `canceled` (before `ends_at`) | Yes until period end | Full until period end |
| `canceled` (after `ends_at`) | No | Hard-gated to `/billing` |

## 4. Trial Expiration Reminders

**`TrialReminderCommand`** (`trial:reminders`) — scheduled daily at 09:00 UTC.

Three `ShouldQueue` mail notifications:
- **`TrialEndingNotification`** (2 days left): "Your trial ends in 2 days."
- **`TrialLastDayNotification`** (1 day left): "Tomorrow is your last day. Here's what you'd miss..."
- **`TrialExpiredNotification`** (expired today): "Your trial has ended. Reactivate anytime."

Query logic:
```php
whereDate('trial_ends_at', today()->addDays(2))  // day 12
whereDate('trial_ends_at', today()->addDays(1))  // day 13
whereDate('trial_ends_at', today())               // day 14
```

All queries filter `whereDoesntHave('subscriptions')` to skip converted users.

## 5. Integration Points

**Changes to existing code:**
- `User` model: Add `isSubscribedOrTrial()` helper. Update `competitorLimit()` to check actual plan (Starter=1, Pro=3) based on subscription price ID.
- `SetupWizard`: Already uses `competitorLimit()` — will reflect plan-aware limits.
- `WeeklyDigestCommand`: Already checks trial/subscription — no changes needed.
- Navigation: Add "Billing" link + trial countdown badge.

**New files:**
- `app/Livewire/BillingPage.php` + Blade view
- `app/Http/Middleware/EnsureSubscribed.php`
- `app/Http/Controllers/StripeWebhookController.php`
- `app/Console/Commands/TrialReminderCommand.php`
- `app/Notifications/TrialEndingNotification.php`
- `app/Notifications/TrialLastDayNotification.php`
- `app/Notifications/TrialExpiredNotification.php`

## 6. Testing Strategy

- **BillingPage**: All 3 states render, subscription creation (mocked Stripe), plan swap, cancellation
- **EnsureSubscribed**: Trial passes, subscribed passes, expired redirects, past_due passes
- **StripeWebhookController**: Payment failed sends notification, subscription deleted sends notification
- **TrialReminderCommand**: Correct notifications at day 12/13/14, skips subscribed users
- **Integration**: Trial → gate → subscribe → access → cancel → gated again

All Stripe API calls mocked via Cashier test helpers / `Http::fake()`.
