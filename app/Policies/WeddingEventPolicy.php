<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WeddingEvent;

class WeddingEventPolicy
{
    public function view(User $user, WeddingEvent $weddingEvent): bool
    {
        return $user->can('view', $weddingEvent->wedding);
    }

    public function create(User $user, WeddingEvent $weddingEvent): bool
    {
        return $user->can('editOperationalData', $weddingEvent->wedding);
    }

    public function update(User $user, WeddingEvent $weddingEvent): bool
    {
        return $user->can('editOperationalData', $weddingEvent->wedding);
    }

    public function delete(User $user, WeddingEvent $weddingEvent): bool
    {
        return $user->can('editOperationalData', $weddingEvent->wedding);
    }
}
