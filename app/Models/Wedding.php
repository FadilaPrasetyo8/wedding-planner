<?php

namespace App\Models;

use App\Enums\WeddingStatus;
use Database\Factories\WeddingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $uuid
 * @property string $name
 * @property string $partner_one_name
 * @property string $partner_two_name
 * @property Carbon $wedding_date
 * @property Carbon|null $planning_start_date
 * @property string $timezone
 * @property string $currency
 * @property int|null $target_budget
 * @property WeddingStatus $status
 */
#[Fillable(['name', 'partner_one_name', 'partner_two_name', 'wedding_date', 'planning_start_date', 'timezone', 'currency', 'target_budget', 'status'])]
class Wedding extends Model
{
    /** @use HasFactory<WeddingFactory> */
    use HasFactory, SoftDeletes;

    protected static function booted(): void
    {
        static::creating(function (Wedding $wedding): void {
            $wedding->uuid ??= (string) Str::uuid();
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /** @return HasMany<WeddingMember, $this> */
    public function memberships(): HasMany
    {
        return $this->hasMany(WeddingMember::class);
    }

    /** @return HasMany<WeddingMember, $this> */
    public function activeMemberships(): HasMany
    {
        return $this->memberships()->whereNull('left_at');
    }

    /** @return BelongsToMany<User, $this> */
    public function activeMembers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'wedding_members')
            ->wherePivotNull('left_at')
            ->withPivot(['id', 'role', 'joined_at', 'left_at'])
            ->withTimestamps();
    }

    /** @return HasMany<WeddingEvent, $this> */
    public function events(): HasMany
    {
        return $this->hasMany(WeddingEvent::class);
    }

    /** @return HasMany<WeddingInvitation, $this> */
    public function invitations(): HasMany
    {
        return $this->hasMany(WeddingInvitation::class);
    }

    /** @return HasMany<ActivityLog, $this> */
    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'wedding_date' => 'date',
            'planning_start_date' => 'date',
            'target_budget' => 'integer',
            'status' => WeddingStatus::class,
        ];
    }
}
