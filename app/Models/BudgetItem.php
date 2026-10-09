<?php

namespace App\Models;

use App\Enums\BudgetItemStatus;
use Database\Factories\BudgetItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $uuid
 * @property int $wedding_id
 * @property int|null $budget_category_id
 * @property int|null $wedding_event_id
 * @property int|null $vendor_id
 * @property string $name
 * @property int $estimated_amount
 * @property int|null $final_amount
 * @property BudgetItemStatus $status
 * @property string|null $notes
 * @property string|null $source_scenario_name
 * @property string|null $source_item_name
 * @property string|null $application_batch_id
 * @property-read Wedding $wedding
 * @property-read BudgetCategory|null $category
 * @property-read WeddingEvent|null $event
 * @property-read Vendor|null $vendor
 */
#[Fillable(['wedding_id', 'budget_category_id', 'wedding_event_id', 'vendor_id', 'name', 'estimated_amount', 'final_amount', 'status', 'notes', 'source_scenario_name', 'source_item_name', 'application_batch_id'])]
class BudgetItem extends Model
{
    /** @use HasFactory<BudgetItemFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::creating(fn (BudgetItem $item) => $item->uuid ??= (string) Str::uuid());
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

    /** @return BelongsTo<BudgetCategory, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(BudgetCategory::class, 'budget_category_id');
    }

    /** @return BelongsTo<WeddingEvent, $this> */
    public function event(): BelongsTo
    {
        return $this->belongsTo(WeddingEvent::class, 'wedding_event_id');
    }

    /** @return BelongsTo<Vendor, $this> */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    /** @return HasMany<PaymentTerm, $this> */
    public function paymentTerms(): HasMany
    {
        return $this->hasMany(PaymentTerm::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'estimated_amount' => 'integer',
            'final_amount' => 'integer',
            'status' => BudgetItemStatus::class,
        ];
    }
}
