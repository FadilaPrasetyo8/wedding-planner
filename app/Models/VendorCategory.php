<?php

namespace App\Models;

use Database\Factories\VendorCategoryFactory;
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
class VendorCategory extends Model
{
    /** @use HasFactory<VendorCategoryFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::creating(fn (VendorCategory $category) => $category->uuid ??= (string) Str::uuid());
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

    /** @return HasMany<Vendor, $this> */
    public function vendors(): HasMany
    {
        return $this->hasMany(Vendor::class);
    }
}
