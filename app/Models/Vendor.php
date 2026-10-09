<?php

namespace App\Models;

use App\Enums\VendorStatus;
use Database\Factories\VendorFactory;
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
 * @property int $vendor_category_id
 * @property string $name
 * @property VendorStatus $status
 * @property int|null $internal_rating
 * @property string|null $address
 * @property string|null $website
 * @property string|null $social_media
 * @property string|null $notes
 * @property-read Wedding $wedding
 * @property-read VendorCategory|null $category
 */
#[Fillable(['wedding_id', 'vendor_category_id', 'name', 'status', 'internal_rating', 'address', 'website', 'social_media', 'notes'])]
class Vendor extends Model
{
    /** @use HasFactory<VendorFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::creating(fn (Vendor $vendor) => $vendor->uuid ??= (string) Str::uuid());
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

    /** @return BelongsTo<VendorCategory, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(VendorCategory::class, 'vendor_category_id');
    }

    /** @return HasMany<VendorContact, $this> */
    public function contacts(): HasMany
    {
        return $this->hasMany(VendorContact::class);
    }

    /** @return HasMany<BudgetItem, $this> */
    public function budgetItems(): HasMany
    {
        return $this->hasMany(BudgetItem::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['status' => VendorStatus::class, 'internal_rating' => 'integer'];
    }
}
