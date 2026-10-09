<?php

namespace App\Policies;

use App\Models\GuestSide;
use App\Models\User;

class GuestSidePolicy
{
    public function view(User $user, GuestSide $side): bool
    {
        return $user->can('view', $side->wedding);
    }

    public function create(User $user, GuestSide $side): bool
    {
        return $user->can('editOperationalData', $side->wedding);
    }

    public function update(User $user, GuestSide $side): bool
    {
        return $user->can('editOperationalData', $side->wedding);
    }

    public function delete(User $user, GuestSide $side): bool
    {
        return $user->can('editOperationalData', $side->wedding);
    }
}
