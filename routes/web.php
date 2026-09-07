<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\WeddingInvitationResponseController;
use App\Http\Controllers\Weddings\TaskCategoryController;
use App\Http\Controllers\Weddings\TaskController;
use App\Http\Controllers\Weddings\TaskStatusController;
use App\Http\Controllers\Weddings\WeddingController;
use App\Http\Controllers\Weddings\WeddingEventController;
use App\Http\Controllers\Weddings\WeddingInvitationController;
use App\Http\Controllers\Weddings\WeddingMemberController;
use App\Http\Controllers\Weddings\WeddingOverviewController;
use App\Http\Controllers\Weddings\WeddingOwnershipController;
use App\Http\Controllers\Weddings\WeddingSettingsController;
use App\Http\Controllers\Weddings\WeddingTimelineController;
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
            Route::scopeBindings()->group(function () {
                Route::resource('events', WeddingEventController::class)
                    ->names('weddings.events');
                Route::get('planning', [TaskController::class, 'index'])->name('weddings.planning.index');
                Route::get('planning/timeline', WeddingTimelineController::class)->name('weddings.planning.timeline');
                Route::get('planning/configuration', [TaskController::class, 'configuration'])->name('weddings.planning.configuration');
                Route::get('planning/create', [TaskController::class, 'create'])->name('weddings.tasks.create');
                Route::post('planning', [TaskController::class, 'store'])->name('weddings.tasks.store');
                Route::get('planning/{task}', [TaskController::class, 'show'])->name('weddings.tasks.show');
                Route::get('planning/{task}/edit', [TaskController::class, 'edit'])->name('weddings.tasks.edit');
                Route::patch('planning/{task}', [TaskController::class, 'update'])->name('weddings.tasks.update');
                Route::delete('planning/{task}', [TaskController::class, 'destroy'])->name('weddings.tasks.destroy');
                Route::post('planning/categories', [TaskCategoryController::class, 'store'])->name('weddings.task-categories.store');
                Route::patch('planning/categories/{task_category}', [TaskCategoryController::class, 'update'])->name('weddings.task-categories.update');
                Route::delete('planning/categories/{task_category}', [TaskCategoryController::class, 'destroy'])->name('weddings.task-categories.destroy');
                Route::post('planning/statuses', [TaskStatusController::class, 'store'])->name('weddings.task-statuses.store');
                Route::patch('planning/statuses/{task_status}', [TaskStatusController::class, 'update'])->name('weddings.task-statuses.update');
                Route::delete('planning/statuses/{task_status}', [TaskStatusController::class, 'destroy'])->name('weddings.task-statuses.destroy');
            });
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
