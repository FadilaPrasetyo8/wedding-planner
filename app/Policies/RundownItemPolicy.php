<?php

namespace App\Policies;

use App\Models\RundownItem;
use App\Models\User;

class RundownItemPolicy
{
    public function view(User $user, RundownItem $item): bool
    {
        return $user->can('view', $item->wedding);
    }

    public function create(User $user, RundownItem $item): bool
    {
        return $user->can('editOperationalData', $item->wedding);
    }

    public function update(User $user, RundownItem $item): bool
    {
        return $user->can('editOperationalData', $item->wedding);
    }

    public function delete(User $user, RundownItem $item): bool
    {
        return $user->can('editOperationalData', $item->wedding);
    }
}
