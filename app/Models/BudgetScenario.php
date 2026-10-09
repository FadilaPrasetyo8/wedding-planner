<?php

namespace App\Models;

use Database\Factories\BudgetScenarioFactory;
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
 * @property string $name
 * @property string|null $description
 * @property int $sort_order
 * @property bool $is_active
 * @property-read Wedding $wedding
 */
#[Fillable(['wedding_id', 'name', 'description', 'sort_order', 'is_active'])]
class BudgetScenario extends Model
{
    /** @use HasFactory<BudgetScenarioFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::creating(fn (BudgetScenario $scenario) => $scenario->uuid ??= (string) Str::uuid());
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

    /** @return HasMany<ScenarioItem, $this> */
    public function scenarioItems(): HasMany
    {
        return $this->hasMany(ScenarioItem::class);
    }

    /** @return HasMany<BudgetItem, $this> */
    public function appliedBudgetItems(): HasMany
    {
        return $this->hasMany(BudgetItem::class, 'source_scenario_name', 'name');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['sort_order' => 'integer', 'is_active' => 'boolean'];
    }
}
