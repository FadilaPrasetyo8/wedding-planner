<?php

namespace App\Models;

use App\Enums\GuestInvitationStatus;
use App\Enums\GuestRsvpStatus;
use Database\Factories\GuestFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $uuid
 * @property int $wedding_id
 * @property int|null $guest_side_id
 * @property int|null $guest_group_id
 * @property string $name
 * @property string|null $phone
 * @property int $invitation_count
 * @property int $pax_count
 * @property GuestInvitationStatus $invitation_status
 * @property GuestRsvpStatus|null $rsvp_status
 * @property string|null $notes
 * @property-read Wedding $wedding
 * @property-read GuestSide|null $side
 * @property-read GuestGroup|null $group
 */
#[Fillable(['wedding_id', 'guest_side_id', 'guest_group_id', 'name', 'phone', 'invitation_count', 'pax_count', 'invitation_status', 'rsvp_status', 'notes'])]
class Guest extends Model
{
    /** @use HasFactory<GuestFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::creating(fn (Guest $guest) => $guest->uuid ??= (string) Str::uuid());
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

    /** @return BelongsTo<GuestSide, $this> */
    public function side(): BelongsTo
    {
        return $this->belongsTo(GuestSide::class, 'guest_side_id');
    }

    /** @return BelongsTo<GuestGroup, $this> */
    public function group(): BelongsTo
    {
        return $this->belongsTo(GuestGroup::class, 'guest_group_id');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'invitation_count' => 'integer',
            'pax_count' => 'integer',
            'invitation_status' => GuestInvitationStatus::class,
            'rsvp_status' => GuestRsvpStatus::class,
        ];
    }
}
