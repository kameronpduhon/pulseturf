<?php

use App\Http\Controllers\DigestFeedbackController;
use App\Http\Controllers\StripeWebhookController;
use App\Livewire\DigestHistory;
use App\Livewire\SettingsPage;
use App\Livewire\SetupWizard;
use Illuminate\Support\Facades\Route;

// Public
Route::view('/', 'welcome');
Route::view('/privacy', 'legal.privacy')->name('privacy');
Route::view('/terms', 'legal.terms')->name('terms');
Route::get("/sample", function () {
    $sections = [
        "performance_snapshot" => "This week, Glow Theory Med Spa received 6 new Google reviews, bringing your total to 312 reviews at a 4.9-star rating. That is up from 306 reviews last Monday.\n\nYour rating held steady at 4.9 ⭐ — no change from last week.\n\nWeekly review velocity: +6 (above your 4-week average of +4.2)",
        "review_highlights" => "⭐⭐⭐⭐⭐ — \"Absolutely love this place. My skin has never looked better. The staff genuinely listens and the results speak for themselves.\" — Sarah M.\n\n⭐⭐⭐⭐⭐ — \"I have been to three different med spas in Scottsdale and Glow Theory is on another level. Worth every penny.\" — James R.\n\n⭐⭐⭐⭐⭐ — \"Quick, professional, and the results were immediate. Already booked my next appointment.\" — Alyssa T.",
        "competitor_watch" => "NakedMD (your tracked competitor) received 11 new reviews this week, bringing their total to 967 reviews at 4.9 stars.\n\nNotable themes in their new reviews:\n• Clients praising fast appointment availability\n• Several mentions of their loyalty rewards program\n• One complaint about wait times on Fridays\n\nOpportunity: NakedMD clients are noticing wait times — if your scheduling is smoother, that is worth highlighting in your responses.",
        "sentiment_trends" => "Your reviews this week were overwhelmingly positive. Top themes:\n\n✅ Staff attentiveness — mentioned in 4 of 6 reviews\n✅ Visible results — mentioned in 3 of 6 reviews\n✅ Easy booking experience — mentioned in 2 of 6 reviews\n\nNo negative themes detected this week.",
        "action_items" => "1. Respond to all 6 new reviews this week — businesses that respond within 48 hours see 12% higher review velocity on average.\n\n2. Consider highlighting your booking experience on social media — it came up organically in multiple reviews and is a differentiator vs. NakedMD.\n\n3. Your Friday availability looks strong — worth calling out if NakedMD clients are complaining about their Friday wait times.",
        "week_ahead" => "Next Monday you will receive your Week 2 digest. Keep an eye on NakedMD this week — they are trending up in volume. One strong week of responses from you could widen the gap on sentiment score."
    ];

    return view("sample", [
        "sections" => $sections,
        "businessName" => "Glow Theory Med Spa",
        "competitor" => "NakedMD",
        "city" => "Scottsdale, AZ",
        "weekOf" => "March 10, 2026",
    ]);
})->name("sample");

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
    Route::get('/digests', DigestHistory::class)->name('digests');
});

require __DIR__.'/auth.php';
