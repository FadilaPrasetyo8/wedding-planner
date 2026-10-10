<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Vendor;

class VendorPolicy
{
    public function view(User $user, Vendor $vendor): bool
    {
        return $user->can('view', $vendor->wedding);
    }

    public function create(User $user, Vendor $vendor): bool
    {
        return $user->can('editOperationalData', $vendor->wedding);
    }

    public function update(User $user, Vendor $vendor): bool
    {
        return $user->can('editOperationalData', $vendor->wedding);
    }

    public function delete(User $user, Vendor $vendor): bool
    {
        return $user->can('editOperationalData', $vendor->wedding);
    }
}
