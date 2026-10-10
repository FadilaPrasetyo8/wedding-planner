<?php

namespace App\Models;

use App\Enums\RundownStatus;
use Database\Factories\RundownItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $uuid
 * @property int $wedding_id
 * @property int $wedding_event_id
 * @property string $title
 * @property string|null $notes
 * @property string|null $pic
 * @property string|null $highlights
 * @property RundownStatus $status
 * @property string|null $start_time
 * @property string|null $end_time
 * @property int $sort_order
 * @property-read Wedding $wedding
 * @property-read WeddingEvent $event
 */
#[Fillable(['wedding_id', 'wedding_event_id', 'title', 'notes', 'pic', 'highlights', 'status', 'start_time', 'end_time', 'sort_order'])]
class RundownItem extends Model
{
    /** @use HasFactory<RundownItemFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::creating(fn (RundownItem $item) => $item->uuid ??= (string) Str::uuid());
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

    /** @return BelongsTo<WeddingEvent, $this> */
    public function event(): BelongsTo
    {
        return $this->belongsTo(WeddingEvent::class, 'wedding_event_id');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'status' => RundownStatus::class,
            'start_time' => 'datetime:H:i:s',
            'end_time' => 'datetime:H:i:s',
            'sort_order' => 'integer',
        ];
    }
}
