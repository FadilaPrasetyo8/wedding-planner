<?php

namespace App\Policies;

use App\Models\ChecklistCategory;
use App\Models\User;

class ChecklistCategoryPolicy
{
    public function view(User $user, ChecklistCategory $category): bool
    {
        return $user->can('view', $category->wedding);
    }

    public function create(User $user, ChecklistCategory $category): bool
    {
        return $user->can('editOperationalData', $category->wedding);
    }

    public function update(User $user, ChecklistCategory $category): bool
    {
        return $user->can('editOperationalData', $category->wedding);
    }

    public function delete(User $user, ChecklistCategory $category): bool
    {
        return $user->can('editOperationalData', $category->wedding);
    }
}
