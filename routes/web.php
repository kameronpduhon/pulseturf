<?php

use App\Http\Controllers\DigestFeedbackController;
use App\Http\Controllers\StripeWebhookController;
use App\Livewire\BillingPage;
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

// Authenticated + Verified (setup not yet required)
// Only accessible when setup is NOT complete; redirect to /home if already done.
// The SetupWizard component handles the hasActiveBusiness() redirect in mount().
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/setup', SetupWizard::class)->name('setup');
});

// Authenticated + Verified + Setup Complete (billing page is accessible regardless of subscription status)
Route::middleware(['auth', 'verified', 'setup.complete'])->group(function () {
    Route::get('/billing', BillingPage::class)->name('billing');
});

// Authenticated + Verified + Setup Complete + Subscribed (or on trial)
Route::middleware(['auth', 'verified', 'setup.complete', 'subscribed'])->group(function () {
    Route::view('/home', 'home')->name('home');

    Route::view('/profile', 'profile')->name('profile');
});

require __DIR__.'/auth.php';
