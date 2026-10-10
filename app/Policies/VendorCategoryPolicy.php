<?php

namespace App\Policies;

use App\Models\User;
use App\Models\VendorCategory;

class VendorCategoryPolicy
{
    public function view(User $user, VendorCategory $category): bool
    {
        return $user->can('view', $category->wedding);
    }

    public function create(User $user, VendorCategory $category): bool
    {
        return $user->can('editOperationalData', $category->wedding);
    }

    public function update(User $user, VendorCategory $category): bool
    {
        return $user->can('editOperationalData', $category->wedding);
    }

    public function delete(User $user, VendorCategory $category): bool
    {
        return $user->can('editOperationalData', $category->wedding);
    }
}
