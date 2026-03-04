<?php

use App\Http\Controllers\DigestFeedbackController;
use App\Livewire\SetupWizard;
use Illuminate\Support\Facades\Route;

// Public
Route::view('/', 'welcome');

// Signed feedback links (accessed from email, no auth required)
Route::get('/digest/{digest}/feedback/{type}', DigestFeedbackController::class)
    ->name('digest.feedback')
    ->middleware('signed');

// /dashboard redirect for any Breeze-generated links
Route::redirect('/dashboard', '/home')->middleware(['auth', 'verified']);

// Authenticated + Verified (setup not yet required)
// Only accessible when setup is NOT complete; redirect to /home if already done.
// The SetupWizard component handles the hasActiveBusiness() redirect in mount().
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/setup', SetupWizard::class)->name('setup');
});

// Authenticated + Verified + Setup Complete
Route::middleware(['auth', 'verified', 'setup.complete'])->group(function () {
    Route::view('/home', 'home')->name('home');

    Route::view('/profile', 'profile')->name('profile');
});

require __DIR__.'/auth.php';
