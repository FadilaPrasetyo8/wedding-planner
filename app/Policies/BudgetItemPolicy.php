<?php

namespace App\Policies;

use App\Models\BudgetItem;
use App\Models\User;

class BudgetItemPolicy
{
    public function view(User $user, BudgetItem $item): bool
    {
        return $user->can('view', $item->wedding);
    }

    public function create(User $user, BudgetItem $item): bool
    {
        return $user->can('editOperationalData', $item->wedding);
    }

    public function update(User $user, BudgetItem $item): bool
    {
        return $user->can('editOperationalData', $item->wedding);
    }

    public function delete(User $user, BudgetItem $item): bool
    {
        return $user->can('editOperationalData', $item->wedding);
    }

    public function commit(User $user, BudgetItem $item): bool
    {
        return $user->can('editOperationalData', $item->wedding);
    }

    public function cancel(User $user, BudgetItem $item): bool
    {
        return $user->can('editOperationalData', $item->wedding);
    }
}
