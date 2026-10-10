<?php

namespace App\Policies;

use App\Enums\WeddingMemberRole;
use App\Models\User;
use App\Models\Wedding;

class WeddingPolicy
{
    public function view(User $user, Wedding $wedding): bool
    {
        return $this->activeMembershipRole($user, $wedding) !== null;
    }

    public function update(User $user, Wedding $wedding): bool
    {
        return $this->activeMembershipRole($user, $wedding) === WeddingMemberRole::Owner;
    }

    public function manageMembers(User $user, Wedding $wedding): bool
    {
        return $this->activeMembershipRole($user, $wedding) === WeddingMemberRole::Owner;
    }

    public function editOperationalData(User $user, Wedding $wedding): bool
    {
        return in_array(
            $this->activeMembershipRole($user, $wedding),
            [WeddingMemberRole::Owner, WeddingMemberRole::Editor],
            true,
        );
    }

    public function export(User $user, Wedding $wedding): bool
    {
        return $this->editOperationalData($user, $wedding);
    }

    private function activeMembershipRole(User $user, Wedding $wedding): ?WeddingMemberRole
    {
        return $wedding->memberships()
            ->where('user_id', $user->id)
            ->whereNull('left_at')
            ->first()
            ?->role;
    }
}
