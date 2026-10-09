<?php

namespace App\Policies;

use App\Models\SavingContribution;
use App\Models\User;

class SavingContributionPolicy
{
    public function view(User $user, SavingContribution $contribution): bool
    {
        return $user->can('view', $contribution->wedding);
    }

    public function create(User $user, SavingContribution $contribution): bool
    {
        return $user->can('editOperationalData', $contribution->wedding);
    }

    public function update(User $user, SavingContribution $contribution): bool
    {
        return $user->can('editOperationalData', $contribution->wedding);
    }

    public function delete(User $user, SavingContribution $contribution): bool
    {
        return $user->can('editOperationalData', $contribution->wedding);
    }
}
