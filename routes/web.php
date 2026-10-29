<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\JobController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ReferralController;
use App\Http\Controllers\ReferralInvitationController;
use App\Http\Controllers\ReferralResumeController;
use App\Http\Controllers\WelcomeController;
use App\Http\Controllers\ContactController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', WelcomeController::class)->name('welcome');
Route::get('/about', fn () => Inertia::render('About'))->name('about');
Route::get('/contact', [ContactController::class, 'show'])->name('contact');
Route::post('/contact', [ContactController::class, 'store'])
    ->middleware('throttle:contact-submission')
    ->name('contact.store');

// Fortify handled post requests for login, registration, password reset, etc.
Route::get('/login', LoginController::class)->name('login');
Route::get('/register', RegisterController::class)->name('register');

Route::get('/dashboard', DashboardController::class)
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth', 'verified'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/dashboard', AdminDashboardController::class)->name('dashboard');
        Route::get('/jobs', [JobController::class, 'index'])->name('jobs.index');
        Route::get('/jobs/create', [JobController::class, 'create'])->name('jobs.create');
        Route::post('/jobs', [JobController::class, 'store'])->name('jobs.store');
        Route::get('/jobs/{job}/edit', [JobController::class, 'edit'])->name('jobs.edit');
        Route::patch('/jobs/{job}', [JobController::class, 'update'])->name('jobs.update');
        Route::get('/referrals', [ReferralController::class, 'index'])->name('referrals.index');
        Route::patch('/referrals/{referral}', [ReferralController::class, 'update'])->name('referrals.update');
    });

Route::middleware(['auth', 'verified'])->group(function () {
    Route::post('/referrals', [ReferralController::class, 'store'])
        ->middleware('throttle:referral-creation')
        ->name('referrals.store');
    Route::post('/referrals/{referral}/resend', [ReferralController::class, 'resend'])
        ->middleware('throttle:referral-resend')
        ->name('referrals.resend');
    Route::get('/referrals/{referral}/resume', ReferralResumeController::class)->name('referrals.resume');
    Route::post('/notifications/read', [NotificationController::class, 'markAllRead'])->name('notifications.read');
});

// Candidates open these from the encrypted link in their invitation email, without logging in.
Route::middleware('throttle:referral-invitation')
    ->prefix('referrals/invitation')
    ->name('referrals.invitation.')
    ->group(function () {
        Route::get('/{token}', [ReferralInvitationController::class, 'show'])->name('show');
        Route::post('/{token}', [ReferralInvitationController::class, 'respond'])->name('respond');
    });

require __DIR__ . '/settings.php';
