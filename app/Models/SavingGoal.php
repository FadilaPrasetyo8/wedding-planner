<?php

namespace App\Models;

use Database\Factories\SavingGoalFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $uuid
 * @property int $wedding_id
 * @property string $name
 * @property int $target_amount
 * @property Carbon|null $target_date
 * @property bool $is_active
 * @property string|null $notes
 * @property-read Wedding $wedding
 */
#[Fillable(['wedding_id', 'name', 'target_amount', 'target_date', 'is_active', 'notes'])]
class SavingGoal extends Model
{
    /** @use HasFactory<SavingGoalFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::creating(fn (SavingGoal $goal) => $goal->uuid ??= (string) Str::uuid());
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

    /** @return HasMany<SavingContribution, $this> */
    public function contributions(): HasMany
    {
        return $this->hasMany(SavingContribution::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['target_amount' => 'integer', 'target_date' => 'date', 'is_active' => 'boolean'];
    }
}
