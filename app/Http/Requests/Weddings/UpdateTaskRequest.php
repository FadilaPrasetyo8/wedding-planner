<?php

namespace App\Http\Requests\Weddings;

use App\Models\Task;
use App\Models\Wedding;

class UpdateTaskRequest extends StoreTaskRequest
{
    public function authorize(): bool
    {
        $wedding = $this->route('wedding');
        $task = $this->route('task');

        return $wedding instanceof Wedding
            && $task instanceof Task
            && $this->user()->can('update', $task);
    }
}
