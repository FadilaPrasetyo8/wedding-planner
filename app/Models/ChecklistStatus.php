<?php

namespace App\Models;

use App\Enums\ChecklistType;
use Database\Factories\ChecklistStatusFactory;
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
 * @property ChecklistType $type
 * @property string $name
 * @property string $color
 * @property bool $is_completed
 * @property int $sort_order
 * @property-read Wedding $wedding
 */
#[Fillable(['wedding_id', 'type', 'name', 'color', 'is_completed', 'sort_order'])]
class ChecklistStatus extends Model
{
    /** @use HasFactory<ChecklistStatusFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::creating(fn (ChecklistStatus $status) => $status->uuid ??= (string) Str::uuid());
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

    /** @return HasMany<ChecklistItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(ChecklistItem::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['type' => ChecklistType::class, 'is_completed' => 'boolean'];
    }
}
