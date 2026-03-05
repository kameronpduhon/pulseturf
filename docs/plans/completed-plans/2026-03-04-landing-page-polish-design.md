# Phase 6: Landing Page & Polish — Design Document

**Date**: 2026-03-04
**Status**: Approved

## Overview

Phase 6 transforms PulseTurf from a functional app into a presentable product. Three workstreams: a public landing page, a unified settings page, and a visual consistency pass across all app pages.

## Decisions

- **Landing page goal**: Functional MVP — clean and professional, not conversion-optimized
- **Visual style**: Light & clean (white backgrounds, subtle accents, professional feel)
- **Settings approach**: Unified Livewire component with tabs (replaces separate profile + billing pages)
- **Email templates**: Keep Laravel MailMessage defaults (no custom HTML)
- **Unsubscribe handling**: Skipped — digests are transactional (part of paid service)
- **App polish**: Consistency pass (colors, spacing, typography) + frontend-design skill for professional UI
- **Landing page architecture**: Single Blade view + Tailwind CSS + Alpine.js (no Livewire)
- **Settings architecture**: Single Livewire component with tabbed sections

## 1. Landing Page

**Route**: `GET /` — replaces current `welcome.blade.php`
**File**: `resources/views/welcome.blade.php`

### Sections (top to bottom)

1. **Sticky nav** — Logo ("PulseTurf"), Login and "Start Free Trial" CTA buttons
2. **Hero** — Headline on core value prop (competitive intelligence for med spas via weekly AI briefings), subheadline, CTA to `/register`
3. **How It Works** — 3 steps with icons:
   - Add your med spa
   - Pick up to 3 competitors
   - Get weekly AI briefings every Monday
4. **Pricing** — Monthly/annual toggle (Alpine.js), two plan cards:
   - Starter: $29/mo ($290/yr), 1 competitor tracked
   - Pro: $79/mo ($790/yr), 3 competitors tracked, "Most Popular" badge
   - Feature comparison list, annual savings callout
   - CTA buttons link to `/register`
5. **FAQ** — Alpine.js accordion, 4-5 questions:
   - What data do you track?
   - How often do I get briefings?
   - Can I cancel anytime?
   - What happens during the free trial?
6. **Footer** — Copyright, login link

### Tech

- Pure HTML + Tailwind CSS in a single Blade file
- Alpine.js for pricing toggle and FAQ accordion (already available via Livewire)
- No Livewire components, no server-side state
- Mobile responsive (Tailwind breakpoints)

## 2. Unified Settings Page

**Route**: `GET /settings` — middleware: auth, verified, setup.complete, subscribed
**Component**: `app/Livewire/SettingsPage.php`
**View**: `resources/views/livewire/settings-page.blade.php`

### Account Tab

- **Name & email**: Text inputs with save button, inline success message
- **Timezone**: Dropdown (common US timezones + full list), save button
- **Password**: Current password + new password + confirmation, save button
- Each section saves independently

### Billing Tab

- Current plan display (name, price, next billing date)
- Swap plan (monthly/annual, Starter/Pro)
- Update payment method (Stripe Elements card input, `wire:ignore`)
- Cancel subscription (confirmation modal, cancel at period end)
- Resume subscription (from grace period)
- Reuses existing BillingPage logic (refactored into SettingsPage or extracted to shared methods)

### Navigation Changes

- Replace "Profile" nav link with "Settings"
- `/profile` redirects to `/settings`
- `/billing` redirects to `/settings` (with billing tab active via query param or hash)
- Remove standalone profile and billing pages from nav

## 3. Email Review

Review existing notification classes for consistent tone and correct links:
- `WelcomeNotification`
- `TrialEndingNotification`
- `TrialLastDayNotification`
- `TrialExpiredNotification`
- `PaymentFailedNotification`
- `SubscriptionCancelledNotification`

No custom templates — keep Laravel MailMessage builder. Ensure:
- Clear subject lines
- Correct CTA links (billing page, login, etc.)
- Consistent tone (professional, concise)

## 4. Visual Consistency Pass

Audit and fix across all app pages:
- **Auth pages** (login, register): Match landing page style
- **Setup wizard**: Consistent card styles, spacing, button colors
- **Home dashboard**: Clean data presentation, consistent with settings page
- **Navigation**: Consistent styling, proper active states

Focus areas:
- Primary accent color used consistently
- Button styles (primary, secondary, danger) standardized
- Card shadows and border radius consistent
- Spacing and padding uniform
- Mobile responsive across all pages
- Use frontend-design skill for professional UI quality

## Out of Scope

- Email unsubscribe handling (digests are transactional)
- Custom HTML email templates (using Laravel defaults)
- Conversion optimization (A/B testing, analytics, etc.)
- End-to-end flow testing (deferred to Phase 7)
