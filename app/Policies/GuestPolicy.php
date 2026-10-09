<?php

namespace App\Policies;

use App\Models\Guest;
use App\Models\User;

class GuestPolicy
{
    public function view(User $user, Guest $guest): bool
    {
        return $user->can('view', $guest->wedding);
    }

    public function create(User $user, Guest $guest): bool
    {
        return $user->can('editOperationalData', $guest->wedding);
    }

    public function update(User $user, Guest $guest): bool
    {
        return $user->can('editOperationalData', $guest->wedding);
    }

    public function delete(User $user, Guest $guest): bool
    {
        return $user->can('editOperationalData', $guest->wedding);
    }
}
