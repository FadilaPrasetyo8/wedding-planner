<?php

namespace App\Models;

use Database\Factories\ScenarioItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $uuid
 * @property int $wedding_id
 * @property int $budget_scenario_id
 * @property int|null $budget_category_id
 * @property int|null $wedding_event_id
 * @property string $name
 * @property int $amount
 * @property string|null $notes
 * @property int $sort_order
 * @property bool $is_active
 * @property-read Wedding $wedding
 * @property-read BudgetScenario $scenario
 * @property-read BudgetCategory|null $category
 * @property-read WeddingEvent|null $event
 */
#[Fillable(['wedding_id', 'budget_scenario_id', 'budget_category_id', 'wedding_event_id', 'name', 'amount', 'notes', 'sort_order', 'is_active'])]
class ScenarioItem extends Model
{
    /** @use HasFactory<ScenarioItemFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::creating(fn (ScenarioItem $item) => $item->uuid ??= (string) Str::uuid());
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

    /** @return BelongsTo<BudgetScenario, $this> */
    public function scenario(): BelongsTo
    {
        return $this->belongsTo(BudgetScenario::class, 'budget_scenario_id');
    }

    /** @return BelongsTo<BudgetScenario, $this> */
    public function budgetScenario(): BelongsTo
    {
        return $this->scenario();
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

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['amount' => 'integer', 'sort_order' => 'integer', 'is_active' => 'boolean'];
    }
}
