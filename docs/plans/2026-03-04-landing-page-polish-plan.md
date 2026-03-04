# Phase 6: Landing Page & Polish — Implementation Plan

**Date**: 2026-03-04
**Design doc**: `docs/plans/2026-03-04-landing-page-polish-design.md`

## Context
PulseTurf has a complete backend (Phases 1-5) but still uses the default Laravel welcome page and has separate profile/billing pages. Phase 6 creates a professional landing page, consolidates settings into a unified page, and does a visual consistency pass. The `frontend-design` skill will be used for all UI work.

## Step 1: Landing Page
**Goal**: Replace `resources/views/welcome.blade.php` with a PulseTurf marketing page.

**File**: `resources/views/welcome.blade.php` (complete rewrite)

**Standalone Blade page** (no `layouts.app` — self-contained like current welcome page):
- `@vite` for Tailwind CSS + JS
- Alpine.js (already bundled via Livewire)

**Sections**:
1. **Sticky nav**: "PulseTurf" logo (text, indigo-600), Login link, "Start Free Trial" CTA → `/register`. `@auth`: show "Dashboard" → `/home` instead
2. **Hero**: Headline ("Competitive Intelligence for Med Spas"), subheadline about weekly AI briefings, CTA → `/register`, "14-day free trial, no credit card required"
3. **How It Works**: 3-step grid (Add your med spa → Pick competitors → Get weekly AI briefings)
4. **Pricing**: Alpine.js monthly/annual toggle, Starter ($29/mo, $290/yr, 1 competitor) and Pro ($79/mo, $790/yr, 3 competitors, "Most Popular"), feature lists, CTA → `/register`
5. **FAQ**: Alpine.js accordion (4-5 questions about data tracking, briefing frequency, cancellation, trial)
6. **Footer**: Copyright, Login link

**Style**: Light & clean, white bg, indigo accent, Figtree font, fully responsive.
**Use `frontend-design` skill** for this view.

**Verify**: Visit `/`, check all sections render, mobile responsive, pricing toggle, FAQ accordion, CTA links work, `@auth` state shows Dashboard.

---

## Step 2: SettingsPage Component (Account Tab)
**Goal**: Create unified settings page with Account tab.

**New files**:
- `app/Livewire/SettingsPage.php`
- `resources/views/livewire/settings-page.blade.php`

**Component properties**:
- `$activeTab` (string, default 'account')
- Account: `$name`, `$email`, `$timezone`, `$currentPassword`, `$newPassword`, `$newPasswordConfirmation`
- Status messages: `$profileSaved`, `$timezoneSaved`, `$passwordSaved`
- Billing (from BillingPage): `$selectedPlan`, `$billingError`, `$processing`, `$showCancelModal`, `$showUpdateCard`, `$setupIntentClientSecret`

**`mount(?string $tab = null)`**: Init account fields from `auth()->user()`, init billing fields (same as BillingPage::mount), set `$activeTab` from query param.

**Account methods**: `updateProfile()`, `updateTimezone()`, `updatePassword()` — each saves independently with validation and inline success messages.

**Timezone dropdown**: Common US timezones at top (NY, Chicago, Denver, LA, Phoenix, Honolulu, Anchorage) + full `timezone_identifiers_list()`.

**View**: Tab nav (Account | Billing), three cards in Account tab (Profile Info, Timezone, Password). Use `x-show` (not `@if`) for tab panels so Stripe Elements DOM is preserved.

**Use `frontend-design` skill** for this view.

---

## Step 3: Billing Tab in SettingsPage
**Goal**: Move billing functionality into the Billing tab.

**Copy all billing methods** from `app/Livewire/BillingPage.php` into SettingsPage:
- `subscribe()`, `swapPlan()`, `updatePaymentMethod()`, `cancelSubscription()`, `resumeSubscription()`
- Private helpers: `getPlans()`, `getPriceId()`, `getPlanKeyFromPriceId()`

**Copy billing view** from `resources/views/livewire/billing-page.blade.php` into the billing tab panel (wrapped in `x-show="activeTab === 'billing'"` with `wire:ignore.self`).

**Stripe Elements handling**: Use `x-show` for tab panels (keeps DOM alive). The `@assets` and `@script` blocks stay at component level. Add `x-init` or `x-effect` on the billing panel to re-mount Stripe Elements when the tab becomes visible (dispatch `init-stripe-elements` event).

**`switchTab(string $tab)`**: Set `$activeTab`, dispatch `init-stripe-elements` browser event when switching to billing.

**Verify**: `/settings?tab=billing` shows billing UI, Stripe Elements mount, all billing operations work, tab switching doesn't break card input.

---

## Step 4: Routes & Navigation
**Goal**: Wire up `/settings` route, update nav, add redirects.

**`routes/web.php`**:
```php
// Settings accessible without subscribed middleware (trial-expired users need billing tab)
Route::middleware(['auth', 'verified', 'setup.complete'])->group(function () {
    Route::get('/settings', SettingsPage::class)->name('settings');
});

// Home still requires subscribed
Route::middleware(['auth', 'verified', 'setup.complete', 'subscribed'])->group(function () {
    Route::view('/home', 'home')->name('home');
});

// Redirects for deprecated routes
Route::redirect('/profile', '/settings')->middleware(['auth', 'verified']);
Route::redirect('/billing', '/settings?tab=billing')->middleware(['auth', 'verified']);
```
Remove old `/billing` and `/profile` routes.

**`app/Http/Middleware/EnsureSubscribed.php`**: Change redirect from `route('billing')` to `route('settings', ['tab' => 'billing'])`.

**`resources/views/livewire/layout/navigation.blade.php`**:
- Desktop nav: Replace "Billing" with "Settings" → `route('settings')`
- Dropdown: Replace "Billing" and "Profile" with single "Settings" link
- Mobile nav: Same changes
- Active state: `request()->routeIs('settings')`

**`resources/views/home.blade.php`**: Update any `route('profile')` / `route('billing')` links to `route('settings')` / `route('settings', ['tab' => 'billing'])`.

**Verify**: Nav shows "Settings", `/profile` redirects, `/billing` redirects, trial-expired users reach settings, subscribed users see both tabs.

---

## Step 5: Email Notification Review
**Goal**: Update CTA links in notifications from `route('billing')` to `route('settings', ['tab' => 'billing'])`.

**Files** (5 notifications need link updates):
- `app/Notifications/TrialEndingNotification.php`
- `app/Notifications/TrialLastDayNotification.php`
- `app/Notifications/TrialExpiredNotification.php`
- `app/Notifications/PaymentFailedNotification.php`
- `app/Notifications/SubscriptionCancelledNotification.php`

`WelcomeNotification.php` links to `route('home')` — no change needed.

**Verify**: Run existing notification tests, verify links render correctly.

---

## Step 6: Visual Consistency Pass
**Goal**: Ensure consistent branding and styling across all app pages.

**Use `frontend-design` skill** for each view update.

**Files**:
1. `resources/views/components/application-logo.blade.php`: Replace Laravel SVG with "PulseTurf" text mark (indigo-600, font-bold)
2. `resources/views/layouts/guest.blade.php`: Update branding to match landing page
3. `resources/views/livewire/pages/auth/register.blade.php`: Add "14-day free trial" messaging
4. `resources/views/livewire/pages/auth/login.blade.php`: Minor polish
5. `resources/views/layouts/app.blade.php`: Ensure title says "PulseTurf"
6. `resources/views/livewire/setup-wizard.blade.php`: Minor consistency fixes if needed
7. `resources/views/home.blade.php`: Polish data presentation

**Verify**: Visual review of all pages, consistent branding, mobile responsive.

---

## Step 7: Tests & Cleanup
**Goal**: Update/write tests, remove deprecated files.

**New test**: `tests/Feature/LandingPageTest.php` — GET `/` returns 200, contains key content, guest vs auth state.

**New test**: `tests/Feature/Livewire/SettingsPageTest.php`:
- Route accessibility (requires auth+verified+setup.complete, does NOT require subscribed)
- Account tab: profile update, timezone update, password change
- Billing tab: renders for trial/subscribed/expired, tab switching
- Mock `createSetupIntent()` like BillingPageTest does

**Update**: Existing tests referencing `route('billing')` or `route('profile')` → update to `route('settings')`.
**Update**: `EnsureSubscribed` middleware test → verify redirect to settings with billing tab.

**Delete deprecated files**:
- `app/Livewire/BillingPage.php`
- `resources/views/livewire/billing-page.blade.php`
- `resources/views/profile.blade.php`

**Update `CLAUDE.md`**: Add Phase 6 section, mark as complete.

**Verify**: `php artisan test` — all tests pass.

---

## Execution Order
Steps 1 and 2 are independent (can parallelize). Then: 3 → 4 → 5 → 6 → 7.

## Key Risk
Stripe Elements in tabbed UI — mitigated by using `x-show` (not `@if`) for tab panels to keep DOM alive, plus event-based re-initialization when switching to billing tab.
