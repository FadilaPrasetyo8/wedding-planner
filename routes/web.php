<?php

use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Http\Controllers\PasswordResetLinkController;
use Laravel\Fortify\Http\Controllers\RegisteredUserController;

Route::middleware('throttle:registration')->post(
    'register',
    [RegisteredUserController::class, 'store'],
)->name('register.store');

Route::middleware('throttle:password-reset')->post(
    'forgot-password',
    [PasswordResetLinkController::class, 'store'],
)->name('password.email');

Route::inertia('/', 'welcome')->name('home');

Route::middleware('auth')->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';
