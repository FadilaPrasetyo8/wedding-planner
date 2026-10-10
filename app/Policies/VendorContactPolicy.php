<?php

namespace App\Policies;

use App\Models\User;
use App\Models\VendorContact;

class VendorContactPolicy
{
    public function view(User $user, VendorContact $contact): bool
    {
        return $user->can('view', $contact->wedding);
    }

    public function create(User $user, VendorContact $contact): bool
    {
        return $user->can('editOperationalData', $contact->wedding);
    }

    public function update(User $user, VendorContact $contact): bool
    {
        return $user->can('editOperationalData', $contact->wedding);
    }

    public function delete(User $user, VendorContact $contact): bool
    {
        return $user->can('editOperationalData', $contact->wedding);
    }
}
