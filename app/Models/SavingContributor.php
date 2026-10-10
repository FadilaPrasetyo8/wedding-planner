<?php

namespace App\Models;

use Database\Factories\SavingContributorFactory;
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
 * @property int|null $wedding_member_id
 * @property string $label
 * @property int $sort_order
 * @property bool $is_active
 * @property-read Wedding $wedding
 * @property-read WeddingMember|null $membership
 */
#[Fillable(['wedding_id', 'wedding_member_id', 'label', 'sort_order', 'is_active'])]
class SavingContributor extends Model
{
    /** @use HasFactory<SavingContributorFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::creating(fn (SavingContributor $contributor) => $contributor->uuid ??= (string) Str::uuid());
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

    /** @return BelongsTo<WeddingMember, $this> */
    public function membership(): BelongsTo
    {
        return $this->belongsTo(WeddingMember::class, 'wedding_member_id');
    }

    /** @return HasMany<SavingContribution, $this> */
    public function contributions(): HasMany
    {
        return $this->hasMany(SavingContribution::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['sort_order' => 'integer', 'is_active' => 'boolean'];
    }
}
