<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\WeddingInvitationResponseController;
use App\Http\Controllers\Weddings\WeddingController;
use App\Http\Controllers\Weddings\WeddingInvitationController;
use App\Http\Controllers\Weddings\WeddingMemberController;
use App\Http\Controllers\Weddings\WeddingOverviewController;
use App\Http\Controllers\Weddings\WeddingOwnershipController;
use App\Http\Controllers\Weddings\WeddingSettingsController;
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
    Route::get('dashboard', DashboardController::class)->name('dashboard');
    Route::get('weddings/create', [WeddingController::class, 'create'])->name('weddings.create');
    Route::post('weddings', [WeddingController::class, 'store'])->name('weddings.store');

    Route::prefix('weddings/{wedding:uuid}')
        ->middleware('wedding.member')
        ->group(function () {
            Route::get('/', [WeddingOverviewController::class, 'show'])->name('weddings.show');
            Route::get('members', [WeddingOverviewController::class, 'members'])->name('weddings.members.index');
            Route::get('settings', [WeddingOverviewController::class, 'settings'])->name('weddings.settings.edit');
            Route::post('invitations', [WeddingInvitationController::class, 'store'])
                ->middleware('verified')
                ->name('weddings.invitations.store');
            Route::delete('invitations/{invitation:uuid}', [WeddingInvitationController::class, 'destroy'])
                ->scopeBindings()
                ->middleware('verified')
                ->name('weddings.invitations.destroy');
            Route::patch('members/{membership}', [WeddingMemberController::class, 'update'])
                ->scopeBindings()
                ->name('weddings.members.update');
            Route::delete('members/{membership}', [WeddingMemberController::class, 'destroy'])
                ->scopeBindings()
                ->name('weddings.members.destroy');
            Route::delete('membership', [WeddingMemberController::class, 'leave'])
                ->name('weddings.members.leave');
            Route::post('ownership', WeddingOwnershipController::class)
                ->name('weddings.ownership.transfer');
            Route::patch('settings', [WeddingSettingsController::class, 'update'])
                ->name('weddings.settings.update');
            Route::delete('settings', [WeddingSettingsController::class, 'destroy'])
                ->name('weddings.settings.destroy');
        });

    Route::prefix('invitations/{token}')->middleware('verified')->group(function () {
        Route::get('/', [WeddingInvitationResponseController::class, 'show'])->name('invitations.show');
        Route::post('accept', [WeddingInvitationResponseController::class, 'accept'])->name('invitations.accept');
        Route::post('decline', [WeddingInvitationResponseController::class, 'decline'])->name('invitations.decline');
    });
});

require __DIR__.'/settings.php';
