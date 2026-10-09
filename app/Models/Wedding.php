<?php

namespace App\Models;

use App\Enums\WeddingStatus;
use Database\Factories\WeddingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $uuid
 * @property string $name
 * @property string $partner_one_name
 * @property string $partner_two_name
 * @property Carbon $wedding_date
 * @property Carbon|null $planning_start_date
 * @property string $timezone
 * @property string $currency
 * @property int|null $target_budget
 * @property WeddingStatus $status
 */
#[Fillable(['name', 'partner_one_name', 'partner_two_name', 'wedding_date', 'planning_start_date', 'timezone', 'currency', 'target_budget', 'status'])]
class Wedding extends Model
{
    /** @use HasFactory<WeddingFactory> */
    use HasFactory, SoftDeletes;

    protected static function booted(): void
    {
        static::creating(function (Wedding $wedding): void {
            $wedding->uuid ??= (string) Str::uuid();
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /** @return HasMany<WeddingMember, $this> */
    public function memberships(): HasMany
    {
        return $this->hasMany(WeddingMember::class);
    }

    /** @return HasMany<WeddingMember, $this> */
    public function activeMemberships(): HasMany
    {
        return $this->memberships()->whereNull('left_at');
    }

    /** @return BelongsToMany<User, $this> */
    public function activeMembers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'wedding_members')
            ->wherePivotNull('left_at')
            ->withPivot(['id', 'role', 'joined_at', 'left_at'])
            ->withTimestamps();
    }

    /** @return HasMany<WeddingEvent, $this> */
    public function events(): HasMany
    {
        return $this->hasMany(WeddingEvent::class);
    }

    /** @return HasMany<TaskCategory, $this> */
    public function taskCategories(): HasMany
    {
        return $this->hasMany(TaskCategory::class);
    }

    /** @return HasMany<TaskStatus, $this> */
    public function taskStatuses(): HasMany
    {
        return $this->hasMany(TaskStatus::class);
    }

    /** @return HasMany<Task, $this> */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    /** @return HasMany<WeddingInvitation, $this> */
    public function invitations(): HasMany
    {
        return $this->hasMany(WeddingInvitation::class);
    }

    /** @return HasMany<ActivityLog, $this> */
    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }

    /** @return HasMany<BudgetCategory, $this> */
    public function budgetCategories(): HasMany
    {
        return $this->hasMany(BudgetCategory::class);
    }

    /** @return HasMany<BudgetScenario, $this> */
    public function budgetScenarios(): HasMany
    {
        return $this->hasMany(BudgetScenario::class);
    }

    /**
     * Alias of budgetScenarios() so scoped route bindings on the {scenario}
     * parameter resolve (Laravel derives the relationship name from the
     * route parameter, not the controller signature).
     *
     * @return HasMany<BudgetScenario, $this>
     */
    public function scenarios(): HasMany
    {
        return $this->budgetScenarios();
    }

    /** @return HasMany<ScenarioItem, $this> */
    public function scenarioItems(): HasMany
    {
        return $this->hasMany(ScenarioItem::class);
    }

    /** @return HasMany<BudgetItem, $this> */
    public function budgetItems(): HasMany
    {
        return $this->hasMany(BudgetItem::class);
    }

    /** @return HasMany<PaymentTerm, $this> */
    public function paymentTerms(): HasMany
    {
        return $this->hasMany(PaymentTerm::class);
    }

    /** @return HasMany<SavingGoal, $this> */
    public function savingGoals(): HasMany
    {
        return $this->hasMany(SavingGoal::class);
    }

    /** @return HasMany<SavingContributor, $this> */
    public function savingContributors(): HasMany
    {
        return $this->hasMany(SavingContributor::class);
    }

    /** @return HasMany<SavingContribution, $this> */
    public function savingContributions(): HasMany
    {
        return $this->hasMany(SavingContribution::class);
    }

    /** @return HasMany<ChecklistGroup, $this> */
    public function checklistGroups(): HasMany
    {
        return $this->hasMany(ChecklistGroup::class);
    }

    /** @return HasMany<ChecklistCategory, $this> */
    public function checklistCategories(): HasMany
    {
        return $this->hasMany(ChecklistCategory::class);
    }

    /** @return HasMany<ChecklistStatus, $this> */
    public function checklistStatuses(): HasMany
    {
        return $this->hasMany(ChecklistStatus::class);
    }

    /** @return HasMany<ChecklistItem, $this> */
    public function checklistItems(): HasMany
    {
        return $this->hasMany(ChecklistItem::class);
    }

    /** @return HasMany<VendorCategory, $this> */
    public function vendorCategories(): HasMany
    {
        return $this->hasMany(VendorCategory::class);
    }

    /** @return HasMany<Vendor, $this> */
    public function vendors(): HasMany
    {
        return $this->hasMany(Vendor::class);
    }

    /** @return HasMany<VendorContact, $this> */
    public function vendorContacts(): HasMany
    {
        return $this->hasMany(VendorContact::class);
    }

    /** @return HasMany<GuestSide, $this> */
    public function guestSides(): HasMany
    {
        return $this->hasMany(GuestSide::class);
    }

    /** @return HasMany<GuestGroup, $this> */
    public function guestGroups(): HasMany
    {
        return $this->hasMany(GuestGroup::class);
    }

    /** @return HasMany<Guest, $this> */
    public function guests(): HasMany
    {
        return $this->hasMany(Guest::class);
    }

    /** @return HasMany<RundownItem, $this> */
    public function rundownItems(): HasMany
    {
        return $this->hasMany(RundownItem::class);
    }

    /** @return HasMany<PlaylistItem, $this> */
    public function playlistItems(): HasMany
    {
        return $this->hasMany(PlaylistItem::class);
    }

    /** @return HasMany<CustomFieldDefinition, $this> */
    public function customFieldDefinitions(): HasMany
    {
        return $this->hasMany(CustomFieldDefinition::class);
    }

    /** @return HasMany<CustomFieldValue, $this> */
    public function customFieldValues(): HasMany
    {
        return $this->hasMany(CustomFieldValue::class);
    }

    /** @return HasMany<Attachment, $this> */
    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'wedding_date' => 'date',
            'planning_start_date' => 'date',
            'target_budget' => 'integer',
            'status' => WeddingStatus::class,
        ];
    }
}
