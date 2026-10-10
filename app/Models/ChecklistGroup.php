<?php

namespace App\Models;

use App\Enums\ChecklistType;
use Database\Factories\ChecklistGroupFactory;
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
 * @property int $sort_order
 * @property-read Wedding $wedding
 */
#[Fillable(['wedding_id', 'type', 'name', 'sort_order'])]
class ChecklistGroup extends Model
{
    /** @use HasFactory<ChecklistGroupFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::creating(fn (ChecklistGroup $group) => $group->uuid ??= (string) Str::uuid());
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
        return ['type' => ChecklistType::class];
    }
}
