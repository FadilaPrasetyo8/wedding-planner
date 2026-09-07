<?php

namespace App\Models;

use Database\Factories\WeddingEventFactory;
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
 * @property string $type
 * @property Carbon $event_date
 * @property string|null $starts_at
 * @property string|null $ends_at
 * @property string|null $venue
 * @property string|null $notes
 * @property bool $is_primary
 * @property int $sort_order
 * @property-read Wedding $wedding
 */
#[Fillable(['wedding_id', 'name', 'type', 'event_date', 'starts_at', 'ends_at', 'venue', 'notes', 'is_primary', 'sort_order'])]
class WeddingEvent extends Model
{
    /** @use HasFactory<WeddingEventFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::creating(fn (WeddingEvent $event) => $event->uuid ??= (string) Str::uuid());
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
        return ['event_date' => 'date', 'is_primary' => 'boolean'];
    }
}
