<?php

namespace App\Policies;

use App\Models\CustomFieldDefinition;
use App\Models\User;

class CustomFieldDefinitionPolicy
{
    public function view(User $user, CustomFieldDefinition $definition): bool
    {
        return $user->can('view', $definition->wedding);
    }

    public function create(User $user, CustomFieldDefinition $definition): bool
    {
        return $user->can('editOperationalData', $definition->wedding);
    }

    public function update(User $user, CustomFieldDefinition $definition): bool
    {
        return $user->can('editOperationalData', $definition->wedding);
    }

    public function delete(User $user, CustomFieldDefinition $definition): bool
    {
        return $user->can('editOperationalData', $definition->wedding);
    }
}
