<?php

namespace App\Models;

use Database\Factories\GuestGroupFactory;
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
 * @property int $sort_order
 * @property-read Wedding $wedding
 */
#[Fillable(['wedding_id', 'name', 'sort_order'])]
class GuestGroup extends Model
{
    /** @use HasFactory<GuestGroupFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::creating(fn (GuestGroup $group) => $group->uuid ??= (string) Str::uuid());
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

    /** @return HasMany<Guest, $this> */
    public function guests(): HasMany
    {
        return $this->hasMany(Guest::class);
    }
}
