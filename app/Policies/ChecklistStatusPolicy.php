<?php

namespace App\Policies;

use App\Models\ChecklistStatus;
use App\Models\User;

class ChecklistStatusPolicy
{
    public function view(User $user, ChecklistStatus $status): bool
    {
        return $user->can('view', $status->wedding);
    }

    public function create(User $user, ChecklistStatus $status): bool
    {
        return $user->can('editOperationalData', $status->wedding);
    }

    public function update(User $user, ChecklistStatus $status): bool
    {
        return $user->can('editOperationalData', $status->wedding);
    }

    public function delete(User $user, ChecklistStatus $status): bool
    {
        return $user->can('editOperationalData', $status->wedding);
    }
}
