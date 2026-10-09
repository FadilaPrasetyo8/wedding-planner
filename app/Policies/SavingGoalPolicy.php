<?php

namespace App\Policies;

use App\Models\SavingGoal;
use App\Models\User;

class SavingGoalPolicy
{
    public function view(User $user, SavingGoal $goal): bool
    {
        return $user->can('view', $goal->wedding);
    }

    public function create(User $user, SavingGoal $goal): bool
    {
        return $user->can('editOperationalData', $goal->wedding);
    }

    public function update(User $user, SavingGoal $goal): bool
    {
        return $user->can('editOperationalData', $goal->wedding);
    }

    public function delete(User $user, SavingGoal $goal): bool
    {
        return $user->can('editOperationalData', $goal->wedding);
    }
}
