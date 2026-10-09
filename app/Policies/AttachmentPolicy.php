<?php

namespace App\Policies;

use App\Models\Attachment;
use App\Models\User;

class AttachmentPolicy
{
    public function view(User $user, Attachment $attachment): bool
    {
        return $user->can('view', $attachment->wedding);
    }

    public function create(User $user, Attachment $attachment): bool
    {
        return $user->can('editOperationalData', $attachment->wedding);
    }

    public function delete(User $user, Attachment $attachment): bool
    {
        return $user->can('editOperationalData', $attachment->wedding);
    }
}
