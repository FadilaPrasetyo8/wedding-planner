<?php

namespace App\Models;

use App\Enums\PaymentTermStatus;
use Database\Factories\PaymentTermFactory;
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
 * @property int $budget_item_id
 * @property string $name
 * @property int $sort_order
 * @property int $planned_amount
 * @property Carbon|null $due_date
 * @property int $actual_paid_amount
 * @property Carbon|null $paid_at
 * @property PaymentTermStatus $status
 * @property string|null $notes
 * @property-read Wedding $wedding
 * @property-read BudgetItem $budgetItem
 */
#[Fillable(['wedding_id', 'budget_item_id', 'name', 'sort_order', 'planned_amount', 'due_date', 'actual_paid_amount', 'paid_at', 'status', 'notes'])]
class PaymentTerm extends Model
{
    /** @use HasFactory<PaymentTermFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::creating(fn (PaymentTerm $term) => $term->uuid ??= (string) Str::uuid());
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

    /** @return BelongsTo<BudgetItem, $this> */
    public function budgetItem(): BelongsTo
    {
        return $this->belongsTo(BudgetItem::class);
    }

    /**
     * Server-derived payment state from the actual settlement, honouring explicit cancellation.
     */
    public function derivedStatus(): PaymentTermStatus
    {
        if ($this->status === PaymentTermStatus::Cancelled) {
            return PaymentTermStatus::Cancelled;
        }

        return match (true) {
            $this->actual_paid_amount >= $this->planned_amount && $this->planned_amount > 0 => PaymentTermStatus::Paid,
            $this->actual_paid_amount > 0 => PaymentTermStatus::PartiallyPaid,
            default => PaymentTermStatus::Planned,
        };
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'planned_amount' => 'integer',
            'due_date' => 'date',
            'actual_paid_amount' => 'integer',
            'paid_at' => 'datetime',
            'status' => PaymentTermStatus::class,
        ];
    }
}
