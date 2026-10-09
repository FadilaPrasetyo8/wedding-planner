<?php

namespace App\Policies;

use App\Models\ChecklistItem;
use App\Models\User;

class ChecklistItemPolicy
{
    public function view(User $user, ChecklistItem $item): bool
    {
        return $user->can('view', $item->wedding);
    }

    public function create(User $user, ChecklistItem $item): bool
    {
        return $user->can('editOperationalData', $item->wedding);
    }

    public function update(User $user, ChecklistItem $item): bool
    {
        return $user->can('editOperationalData', $item->wedding);
    }

    public function delete(User $user, ChecklistItem $item): bool
    {
        return $user->can('editOperationalData', $item->wedding);
    }
}
