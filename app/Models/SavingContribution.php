<?php

namespace App\Models;

use Database\Factories\SavingContributionFactory;
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
 * @property int $saving_goal_id
 * @property int $saving_contributor_id
 * @property int $amount
 * @property Carbon|null $contributed_on
 * @property string|null $source
 * @property string|null $notes
 * @property-read Wedding $wedding
 * @property-read SavingGoal $goal
 * @property-read SavingContributor $contributor
 */
#[Fillable(['wedding_id', 'saving_goal_id', 'saving_contributor_id', 'amount', 'contributed_on', 'source', 'notes'])]
class SavingContribution extends Model
{
    /** @use HasFactory<SavingContributionFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::creating(fn (SavingContribution $contribution) => $contribution->uuid ??= (string) Str::uuid());
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

    /** @return BelongsTo<SavingGoal, $this> */
    public function goal(): BelongsTo
    {
        return $this->belongsTo(SavingGoal::class, 'saving_goal_id');
    }

    /** @return BelongsTo<SavingContributor, $this> */
    public function contributor(): BelongsTo
    {
        return $this->belongsTo(SavingContributor::class, 'saving_contributor_id');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['amount' => 'integer', 'contributed_on' => 'date'];
    }
}
