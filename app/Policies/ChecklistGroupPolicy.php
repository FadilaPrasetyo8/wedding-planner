<?php

namespace App\Policies;

use App\Models\ChecklistGroup;
use App\Models\User;

class ChecklistGroupPolicy
{
    public function view(User $user, ChecklistGroup $group): bool
    {
        return $user->can('view', $group->wedding);
    }

    public function create(User $user, ChecklistGroup $group): bool
    {
        return $user->can('editOperationalData', $group->wedding);
    }

    public function update(User $user, ChecklistGroup $group): bool
    {
        return $user->can('editOperationalData', $group->wedding);
    }

    public function delete(User $user, ChecklistGroup $group): bool
    {
        return $user->can('editOperationalData', $group->wedding);
    }
}
