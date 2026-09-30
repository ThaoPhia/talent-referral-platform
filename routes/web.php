<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\JobController;
use App\Http\Controllers\ReferralController;
use App\Http\Controllers\WelcomeController;
use Illuminate\Support\Facades\Route;

Route::get('/', WelcomeController::class)->name('welcome');

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
    Route::post('/referrals', [ReferralController::class, 'store'])->name('referrals.store');
});

require __DIR__ . '/settings.php';
