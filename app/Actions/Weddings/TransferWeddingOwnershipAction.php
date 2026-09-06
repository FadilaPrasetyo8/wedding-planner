<?php

namespace App\Actions\Weddings;

use App\Enums\WeddingMemberRole;
use App\Models\User;
use App\Models\Wedding;
use App\Models\WeddingMember;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TransferWeddingOwnershipAction
{
    public function execute(Wedding $wedding, User $currentOwner, int $targetMembershipId): void
    {
        DB::transaction(function () use ($wedding, $currentOwner, $targetMembershipId): void {
            Wedding::query()->whereKey($wedding->id)->lockForUpdate()->firstOrFail();
            $memberships = WeddingMember::query()
                ->where('wedding_id', $wedding->id)
                ->lockForUpdate()
                ->get();

            $ownerMembership = $memberships->first(fn (WeddingMember $membership) => $membership->user_id === $currentOwner->id
                && $membership->left_at === null
                && $membership->role === WeddingMemberRole::Owner);
            $target = $memberships->firstWhere('id', $targetMembershipId);

            if (! $ownerMembership || ! $target || $target->left_at !== null || $target->id === $ownerMembership->id) {
                throw ValidationException::withMessages([
                    'membership_id' => 'Pilih anggota aktif lain sebagai pemilik baru.',
                ]);
            }

            $ownerMembership->update(['role' => WeddingMemberRole::Editor]);
            $target->update(['role' => WeddingMemberRole::Owner]);
        });
    }
}
