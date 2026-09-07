<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;

class TaskPolicy
{
    public function view(User $user, Task $task): bool
    {
        return $user->can('view', $task->wedding);
    }

    public function create(User $user, Task $task): bool
    {
        return $user->can('editOperationalData', $task->wedding);
    }

    public function update(User $user, Task $task): bool
    {
        return $user->can('editOperationalData', $task->wedding);
    }

    public function delete(User $user, Task $task): bool
    {
        return $user->can('editOperationalData', $task->wedding);
    }
}
