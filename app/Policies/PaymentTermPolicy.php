<?php

namespace App\Policies;

use App\Models\PaymentTerm;
use App\Models\User;

class PaymentTermPolicy
{
    public function view(User $user, PaymentTerm $term): bool
    {
        return $user->can('view', $term->wedding);
    }

    public function create(User $user, PaymentTerm $term): bool
    {
        return $user->can('editOperationalData', $term->wedding);
    }

    public function update(User $user, PaymentTerm $term): bool
    {
        return $user->can('editOperationalData', $term->wedding);
    }

    public function delete(User $user, PaymentTerm $term): bool
    {
        return $user->can('editOperationalData', $term->wedding);
    }
}
