<?php

use App\Http\Controllers\DigestFeedbackController;
use App\Http\Controllers\StripeWebhookController;
use App\Livewire\SettingsPage;
use App\Livewire\SetupWizard;
use Illuminate\Support\Facades\Route;

// Public
Route::view('/', 'welcome');

// Signed feedback links (accessed from email, no auth required)
Route::get('/digest/{digest}/feedback/{type}', DigestFeedbackController::class)
    ->name('digest.feedback')
    ->middleware('signed');

// Stripe webhook (public — Cashier verifies the Stripe signature internally)
Route::post('/stripe/webhook', [StripeWebhookController::class, 'handleWebhook'])
    ->name('cashier.webhook');

// /dashboard redirect for any Breeze-generated links
Route::redirect('/dashboard', '/home')->middleware(['auth', 'verified']);

// Legacy redirects — /profile and /billing now live at /settings
Route::redirect('/profile', '/settings')->middleware(['auth', 'verified']);
Route::redirect('/billing', '/settings?tab=billing')->middleware(['auth', 'verified']);

// Authenticated + Verified (setup not yet required)
// Only accessible when setup is NOT complete; redirect to /home if already done.
// The SetupWizard component handles the hasActiveBusiness() redirect in mount().
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/setup', SetupWizard::class)->name('setup');
});

// Authenticated + Verified + Setup Complete
// /settings is NOT behind the subscribed middleware — trial-expired users need billing access
Route::middleware(['auth', 'verified', 'setup.complete'])->group(function () {
    Route::get('/settings', SettingsPage::class)->name('settings');
});

// Authenticated + Verified + Setup Complete + Subscribed (or on trial)
Route::middleware(['auth', 'verified', 'setup.complete', 'subscribed'])->group(function () {
    Route::view('/home', 'home')->name('home');
});

require __DIR__.'/auth.php';
