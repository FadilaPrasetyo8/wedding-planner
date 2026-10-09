<?php

namespace App\Policies;

use App\Models\BudgetScenario;
use App\Models\User;

class BudgetScenarioPolicy
{
    public function view(User $user, BudgetScenario $scenario): bool
    {
        return $user->can('view', $scenario->wedding);
    }

    public function create(User $user, BudgetScenario $scenario): bool
    {
        return $user->can('editOperationalData', $scenario->wedding);
    }

    public function update(User $user, BudgetScenario $scenario): bool
    {
        return $user->can('editOperationalData', $scenario->wedding);
    }

    public function delete(User $user, BudgetScenario $scenario): bool
    {
        return $user->can('editOperationalData', $scenario->wedding);
    }
}
