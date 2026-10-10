<?php

namespace App\Policies;

use App\Models\ScenarioItem;
use App\Models\User;

class ScenarioItemPolicy
{
    public function view(User $user, ScenarioItem $item): bool
    {
        return $user->can('view', $item->wedding);
    }

    public function create(User $user, ScenarioItem $item): bool
    {
        return $user->can('editOperationalData', $item->wedding);
    }

    public function update(User $user, ScenarioItem $item): bool
    {
        return $user->can('editOperationalData', $item->wedding);
    }

    public function delete(User $user, ScenarioItem $item): bool
    {
        return $user->can('editOperationalData', $item->wedding);
    }
}
