<?php

use App\Http\Controllers\CustomFields\CustomFieldDefinitionController;
use App\Http\Controllers\CustomFields\CustomFieldValueController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EventDay\PlaylistController;
use App\Http\Controllers\EventDay\RundownController;
use App\Http\Controllers\Exports\WeddingExportController;
use App\Http\Controllers\Files\AttachmentController;
use App\Http\Controllers\Finance\BudgetItemController;
use App\Http\Controllers\Health\ReadinessController;
use App\Http\Controllers\Finance\BudgetScenarioController;
use App\Http\Controllers\Finance\FinanceOverviewController;
use App\Http\Controllers\Finance\PaymentTermController;
use App\Http\Controllers\Finance\SavingContributionController;
use App\Http\Controllers\Finance\SavingContributorController;
use App\Http\Controllers\Finance\SavingGoalController;
use App\Http\Controllers\Finance\ScenarioItemController;
use App\Http\Controllers\Logistics\ChecklistController;
use App\Http\Controllers\Logistics\GuestController;
use App\Http\Controllers\Logistics\VendorCategoryController;
use App\Http\Controllers\Logistics\VendorContactController;
use App\Http\Controllers\Logistics\VendorController;
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
Route::get('ready', ReadinessController::class)->name('ready');

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
                Route::get('exports/budget', [WeddingExportController::class, 'budget'])->name('weddings.exports.budget');
                Route::get('exports/guests', [WeddingExportController::class, 'guests'])->name('weddings.exports.guests');
                Route::get('exports/planning', [WeddingExportController::class, 'planning'])->name('weddings.exports.planning');
                Route::get('exports/checklist/{type}', [WeddingExportController::class, 'checklist'])
                    ->whereIn('type', ['seserahan', 'administration'])
                    ->name('weddings.exports.checklist');
                Route::get('exports/rundown', [WeddingExportController::class, 'rundown'])->name('weddings.exports.rundown');
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

                Route::get('finance', [FinanceOverviewController::class, 'show'])->name('weddings.finance.index');
                Route::get('finance/scenarios', [BudgetScenarioController::class, 'index'])->name('weddings.finance.scenarios.index');
                Route::post('finance/scenarios', [BudgetScenarioController::class, 'store'])->name('weddings.finance.scenarios.store');
                Route::get('finance/scenarios/{scenario}', [BudgetScenarioController::class, 'show'])->name('weddings.finance.scenarios.show');
                Route::patch('finance/scenarios/{scenario}', [BudgetScenarioController::class, 'update'])->name('weddings.finance.scenarios.update');
                Route::post('finance/scenarios/{scenario}/copy', [BudgetScenarioController::class, 'copy'])->name('weddings.finance.scenarios.copy');
                Route::post('finance/scenarios/{scenario}/apply', [BudgetScenarioController::class, 'apply'])->name('weddings.finance.scenarios.apply');
                Route::delete('finance/scenarios/{scenario}', [BudgetScenarioController::class, 'destroy'])->name('weddings.finance.scenarios.destroy');
                Route::post('finance/scenarios/{scenario}/items', [ScenarioItemController::class, 'store'])->name('weddings.finance.scenario-items.store');
                Route::patch('finance/scenarios/{scenario}/items/{scenario_item}', [ScenarioItemController::class, 'update'])->name('weddings.finance.scenario-items.update');
                Route::delete('finance/scenarios/{scenario}/items/{scenario_item}', [ScenarioItemController::class, 'destroy'])->name('weddings.finance.scenario-items.destroy');
                Route::get('finance/budget', [BudgetItemController::class, 'index'])->name('weddings.finance.budget.index');
                Route::post('finance/budget', [BudgetItemController::class, 'store'])->name('weddings.finance.budget.store');
                Route::get('finance/budget/{budget_item}', [BudgetItemController::class, 'show'])->name('weddings.finance.budget.show');
                Route::patch('finance/budget/{budget_item}', [BudgetItemController::class, 'update'])->name('weddings.finance.budget.update');
                Route::post('finance/budget/{budget_item}/commit', [BudgetItemController::class, 'commit'])->name('weddings.finance.budget.commit');
                Route::post('finance/budget/{budget_item}/cancel', [BudgetItemController::class, 'cancel'])->name('weddings.finance.budget.cancel');
                Route::delete('finance/budget/{budget_item}', [BudgetItemController::class, 'destroy'])->name('weddings.finance.budget.destroy');
                Route::post('finance/budget/{budget_item}/terms', [PaymentTermController::class, 'store'])->name('weddings.finance.payment-terms.store');
                Route::patch('finance/budget/{budget_item}/terms/{payment_term}', [PaymentTermController::class, 'update'])->name('weddings.finance.payment-terms.update');
                Route::post('finance/budget/{budget_item}/terms/{payment_term}/payment', [PaymentTermController::class, 'recordPayment'])->name('weddings.finance.payment-terms.payment');
                Route::post('finance/budget/{budget_item}/terms/reorder', [PaymentTermController::class, 'reorder'])->name('weddings.finance.payment-terms.reorder');
                Route::delete('finance/budget/{budget_item}/terms/{payment_term}', [PaymentTermController::class, 'destroy'])->name('weddings.finance.payment-terms.destroy');
                Route::get('finance/savings', [SavingGoalController::class, 'index'])->name('weddings.finance.savings.index');
                Route::post('finance/savings', [SavingGoalController::class, 'store'])->name('weddings.finance.savings.store');
                Route::patch('finance/savings/{saving_goal}', [SavingGoalController::class, 'update'])->name('weddings.finance.savings.update');
                Route::delete('finance/savings/{saving_goal}', [SavingGoalController::class, 'destroy'])->name('weddings.finance.savings.destroy');
                Route::get('finance/savings/contributors', [SavingContributorController::class, 'index'])->name('weddings.finance.savings.contributors');
                Route::post('finance/savings/contributors', [SavingContributorController::class, 'store'])->name('weddings.finance.savings.contributors.store');
                Route::patch('finance/savings/contributors/{saving_contributor}', [SavingContributorController::class, 'update'])->name('weddings.finance.savings.contributors.update');
                Route::delete('finance/savings/contributors/{saving_contributor}', [SavingContributorController::class, 'destroy'])->name('weddings.finance.savings.contributors.destroy');
                Route::get('finance/savings/contributions', [SavingContributionController::class, 'index'])->name('weddings.finance.savings.contributions');
                Route::post('finance/savings/contributions', [SavingContributionController::class, 'store'])->name('weddings.finance.savings.contributions.store');
                Route::patch('finance/savings/contributions/{saving_contribution}', [SavingContributionController::class, 'update'])->name('weddings.finance.savings.contributions.update');
                Route::delete('finance/savings/contributions/{saving_contribution}', [SavingContributionController::class, 'destroy'])->name('weddings.finance.savings.contributions.destroy');

                Route::get('checklists/{type}', [ChecklistController::class, 'index'])
                    ->whereIn('type', ['seserahan', 'administration'])
                    ->name('weddings.checklists.index');
                Route::post('checklists/{type}/items', [ChecklistController::class, 'store'])
                    ->whereIn('type', ['seserahan', 'administration'])
                    ->name('weddings.checklist-items.store');
                Route::patch('checklists/{type}/items/{checklist_item}', [ChecklistController::class, 'update'])
                    ->whereIn('type', ['seserahan', 'administration'])
                    ->name('weddings.checklist-items.update');
                Route::delete('checklists/{type}/items/{checklist_item}', [ChecklistController::class, 'destroy'])
                    ->whereIn('type', ['seserahan', 'administration'])
                    ->name('weddings.checklist-items.destroy');
                Route::post('checklists/{type}/references/{reference}', [ChecklistController::class, 'storeReference'])
                    ->whereIn('type', ['seserahan', 'administration'])
                    ->whereIn('reference', ['statuses', 'categories'])
                    ->name('weddings.checklist-references.store');
                Route::delete('checklists/{type}/references/{reference}/{uuid}', [ChecklistController::class, 'destroyReference'])
                    ->whereIn('type', ['seserahan', 'administration'])
                    ->whereIn('reference', ['statuses', 'categories'])
                    ->name('weddings.checklist-references.destroy');

                Route::get('vendors', [VendorController::class, 'index'])->name('weddings.vendors.index');
                Route::post('vendors', [VendorController::class, 'store'])->name('weddings.vendors.store');
                Route::patch('vendors/{vendor}', [VendorController::class, 'update'])->name('weddings.vendors.update');
                Route::delete('vendors/{vendor}', [VendorController::class, 'destroy'])->name('weddings.vendors.destroy');
                Route::post('vendors/categories', [VendorCategoryController::class, 'store'])->name('weddings.vendor-categories.store');
                Route::delete('vendors/categories/{vendorCategory}', [VendorCategoryController::class, 'destroy'])->name('weddings.vendor-categories.destroy');
                Route::post('vendors/{vendor}/contacts', [VendorContactController::class, 'store'])->name('weddings.vendor-contacts.store');
                Route::patch('vendors/{vendor}/contacts/{contact}', [VendorContactController::class, 'update'])->name('weddings.vendor-contacts.update');
                Route::delete('vendors/{vendor}/contacts/{contact}', [VendorContactController::class, 'destroy'])->name('weddings.vendor-contacts.destroy');

                Route::get('guests', [GuestController::class, 'index'])->name('weddings.guests.index');
                Route::post('guests', [GuestController::class, 'store'])->name('weddings.guests.store');
                Route::patch('guests/{guest}', [GuestController::class, 'update'])->name('weddings.guests.update');
                Route::delete('guests/{guest}', [GuestController::class, 'destroy'])->name('weddings.guests.destroy');
                Route::post('guests/references/{reference}', [GuestController::class, 'storeReference'])
                    ->whereIn('reference', ['sides', 'groups'])
                    ->name('weddings.guest-references.store');
                Route::delete('guests/references/{reference}/{uuid}', [GuestController::class, 'destroyReference'])
                    ->whereIn('reference', ['sides', 'groups'])
                    ->name('weddings.guest-references.destroy');

                Route::get('event-day/rundown', [RundownController::class, 'index'])->name('weddings.rundown.index');
                Route::get('event-day/rundown/print', [RundownController::class, 'print'])->name('weddings.rundown.print');
                Route::post('event-day/rundown', [RundownController::class, 'store'])->name('weddings.rundown.store');
                Route::patch('event-day/rundown/{rundown_item}', [RundownController::class, 'update'])->name('weddings.rundown.update');
                Route::post('event-day/rundown/reorder', [RundownController::class, 'reorder'])->name('weddings.rundown.reorder');
                Route::delete('event-day/rundown/{rundown_item}', [RundownController::class, 'destroy'])->name('weddings.rundown.destroy');
                Route::get('event-day/playlist', [PlaylistController::class, 'index'])->name('weddings.playlist.index');
                Route::post('event-day/playlist', [PlaylistController::class, 'store'])->name('weddings.playlist.store');
                Route::patch('event-day/playlist/{playlist_item}', [PlaylistController::class, 'update'])->name('weddings.playlist.update');
                Route::post('event-day/playlist/reorder', [PlaylistController::class, 'reorder'])->name('weddings.playlist.reorder');
                Route::delete('event-day/playlist/{playlist_item}', [PlaylistController::class, 'destroy'])->name('weddings.playlist.destroy');

                Route::get('custom-fields', [CustomFieldDefinitionController::class, 'index'])->name('weddings.custom-fields.index');
                Route::post('custom-fields', [CustomFieldDefinitionController::class, 'store'])->name('weddings.custom-fields.store');
                Route::patch('custom-fields/values', [CustomFieldValueController::class, 'update'])->name('weddings.custom-field-values.update');
                Route::patch('custom-fields/{custom_field_definition}', [CustomFieldDefinitionController::class, 'update'])->name('weddings.custom-fields.update');
                Route::delete('custom-fields/{custom_field_definition}', [CustomFieldDefinitionController::class, 'destroy'])->name('weddings.custom-fields.destroy');

                Route::post('attachments', [AttachmentController::class, 'store'])
                    ->middleware('verified')
                    ->name('weddings.attachments.store');
                Route::get('attachments/{attachment}/download', [AttachmentController::class, 'download'])->name('weddings.attachments.download');
                Route::delete('attachments/{attachment}', [AttachmentController::class, 'destroy'])
                    ->middleware('verified')
                    ->name('weddings.attachments.destroy');
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
