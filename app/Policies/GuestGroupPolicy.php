<?php

namespace App\Policies;

use App\Models\GuestGroup;
use App\Models\User;

class GuestGroupPolicy
{
    public function view(User $user, GuestGroup $group): bool
    {
        return $user->can('view', $group->wedding);
    }

    public function create(User $user, GuestGroup $group): bool
    {
        return $user->can('editOperationalData', $group->wedding);
    }

    public function update(User $user, GuestGroup $group): bool
    {
        return $user->can('editOperationalData', $group->wedding);
    }

    public function delete(User $user, GuestGroup $group): bool
    {
        return $user->can('editOperationalData', $group->wedding);
    }
}
