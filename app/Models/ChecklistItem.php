<?php

namespace App\Models;

use App\Enums\ChecklistType;
use Database\Factories\ChecklistItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $uuid
 * @property int $wedding_id
 * @property ChecklistType $type
 * @property int|null $checklist_group_id
 * @property int|null $checklist_category_id
 * @property int|null $checklist_status_id
 * @property int|null $assignee_member_id
 * @property string $name
 * @property string|null $notes
 * @property int $quantity
 * @property int|null $estimated_cost
 * @property int|null $actual_cost
 * @property Carbon|null $deadline
 * @property Carbon|null $completed_at
 * @property string|null $purchase_url
 * @property-read Wedding $wedding
 * @property-read ChecklistGroup|null $group
 * @property-read ChecklistCategory|null $category
 * @property-read ChecklistStatus|null $status
 * @property-read WeddingMember|null $assignee
 */
#[Fillable(['wedding_id', 'type', 'checklist_group_id', 'checklist_category_id', 'checklist_status_id', 'assignee_member_id', 'name', 'notes', 'quantity', 'estimated_cost', 'actual_cost', 'deadline', 'completed_at', 'purchase_url'])]
class ChecklistItem extends Model
{
    /** @use HasFactory<ChecklistItemFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::creating(fn (ChecklistItem $item) => $item->uuid ??= (string) Str::uuid());
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /** @return BelongsTo<Wedding, $this> */
    public function wedding(): BelongsTo
    {
        return $this->belongsTo(Wedding::class);
    }

    /** @return BelongsTo<ChecklistGroup, $this> */
    public function group(): BelongsTo
    {
        return $this->belongsTo(ChecklistGroup::class, 'checklist_group_id');
    }

    /** @return BelongsTo<ChecklistCategory, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ChecklistCategory::class, 'checklist_category_id');
    }

    /** @return BelongsTo<ChecklistStatus, $this> */
    public function status(): BelongsTo
    {
        return $this->belongsTo(ChecklistStatus::class, 'checklist_status_id');
    }

    /** @return BelongsTo<WeddingMember, $this> */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(WeddingMember::class, 'assignee_member_id');
    }

    /**
     * Estimated total for this item: unit estimate multiplied by quantity.
     */
    public function estimatedTotal(): int
    {
        return ($this->estimated_cost ?? 0) * $this->quantity;
    }

    /**
     * Actual total for this item: unit actual cost multiplied by quantity.
     */
    public function actualTotal(): int
    {
        return ($this->actual_cost ?? 0) * $this->quantity;
    }

    /**
     * An item is overdue when its deadline has passed and its status is not a
     * completed status (or it has no status yet).
     */
    public function isOverdue(): bool
    {
        if ($this->deadline === null || $this->completed_at !== null) {
            return false;
        }

        return $this->deadline->isPast();
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'type' => ChecklistType::class,
            'quantity' => 'integer',
            'estimated_cost' => 'integer',
            'actual_cost' => 'integer',
            'deadline' => 'date',
            'completed_at' => 'datetime',
        ];
    }
}
