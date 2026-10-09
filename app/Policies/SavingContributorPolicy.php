<?php

namespace App\Policies;

use App\Models\SavingContributor;
use App\Models\User;

class SavingContributorPolicy
{
    public function view(User $user, SavingContributor $contributor): bool
    {
        return $user->can('view', $contributor->wedding);
    }

    public function create(User $user, SavingContributor $contributor): bool
    {
        return $user->can('editOperationalData', $contributor->wedding);
    }

    public function update(User $user, SavingContributor $contributor): bool
    {
        return $user->can('editOperationalData', $contributor->wedding);
    }

    public function delete(User $user, SavingContributor $contributor): bool
    {
        return $user->can('editOperationalData', $contributor->wedding);
    }
}
