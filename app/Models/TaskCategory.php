<?php

namespace App\Models;

use Database\Factories\TaskCategoryFactory;
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
 * @property string $color
 * @property int $sort_order
 * @property bool $is_active
 * @property-read Wedding $wedding
 */
#[Fillable(['wedding_id', 'name', 'color', 'sort_order', 'is_active'])]
class TaskCategory extends Model
{
    /** @use HasFactory<TaskCategoryFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::creating(fn (TaskCategory $category) => $category->uuid ??= (string) Str::uuid());
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

    /** @return HasMany<Task, $this> */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }
}
