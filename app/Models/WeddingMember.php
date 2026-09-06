<?php

namespace App\Models;

use App\Enums\WeddingMemberRole;
use Database\Factories\WeddingMemberFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $wedding_id
 * @property int $user_id
 * @property WeddingMemberRole $role
 * @property Carbon $joined_at
 * @property Carbon|null $left_at
 * @property-read Wedding $wedding
 * @property-read User $user
 */
#[Fillable(['wedding_id', 'user_id', 'role', 'joined_at', 'left_at'])]
class WeddingMember extends Model
{
    /** @use HasFactory<WeddingMemberFactory> */
    use HasFactory;

    /** @return BelongsTo<Wedding, $this> */
    public function wedding(): BelongsTo
    {
        return $this->belongsTo(Wedding::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @param Builder<WeddingMember> $query
     * @return Builder<WeddingMember>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('left_at');
    }

    public function isOwner(): bool
    {
        return $this->role === WeddingMemberRole::Owner;
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'role' => WeddingMemberRole::class,
            'joined_at' => 'datetime',
            'left_at' => 'datetime',
        ];
    }
}
