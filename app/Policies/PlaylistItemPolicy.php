<?php

namespace App\Policies;

use App\Models\PlaylistItem;
use App\Models\User;

class PlaylistItemPolicy
{
    public function view(User $user, PlaylistItem $item): bool
    {
        return $user->can('view', $item->wedding);
    }

    public function create(User $user, PlaylistItem $item): bool
    {
        return $user->can('editOperationalData', $item->wedding);
    }

    public function update(User $user, PlaylistItem $item): bool
    {
        return $user->can('editOperationalData', $item->wedding);
    }

    public function delete(User $user, PlaylistItem $item): bool
    {
        return $user->can('editOperationalData', $item->wedding);
    }
}
