<?php

namespace App\Models;

use Database\Factories\VendorContactFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $uuid
 * @property int $wedding_id
 * @property int $vendor_id
 * @property string $name
 * @property string|null $phone
 * @property string|null $email
 * @property string|null $role
 * @property bool $is_primary
 * @property-read Wedding $wedding
 * @property-read Vendor $vendor
 */
#[Fillable(['wedding_id', 'vendor_id', 'name', 'phone', 'email', 'role', 'is_primary'])]
class VendorContact extends Model
{
    /** @use HasFactory<VendorContactFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::creating(fn (VendorContact $contact) => $contact->uuid ??= (string) Str::uuid());
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

    /** @return BelongsTo<Vendor, $this> */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['is_primary' => 'boolean'];
    }
}
