<?php

namespace App\Http\Requests\Weddings;

use App\Enums\TaskDateMode;
use App\Enums\TaskPriority;
use App\Models\Task;
use App\Models\Wedding;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        $wedding = $this->route('wedding');

        return $wedding instanceof Wedding
            && $this->user()->can('create', new Task(['wedding_id' => $wedding->id]));
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $wedding = $this->route('wedding');
        $weddingId = $wedding instanceof Wedding ? $wedding->id : 0;

        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:10000'],
            'task_category_id' => ['required', 'uuid', Rule::exists('task_categories', 'uuid')->where('wedding_id', $weddingId)],
            'task_status_id' => ['required', 'uuid', Rule::exists('task_statuses', 'uuid')->where('wedding_id', $weddingId)],
            'priority' => ['required', Rule::enum(TaskPriority::class)],
            'assignee_membership_id' => ['nullable', 'integer', Rule::exists('wedding_members', 'id')->where(fn ($query) => $query->where('wedding_id', $weddingId)->whereNull('left_at'))],
            'wedding_event_id' => ['nullable', 'uuid', Rule::exists('wedding_events', 'uuid')->where('wedding_id', $weddingId)],
            'date_mode' => ['required', Rule::enum(TaskDateMode::class)],
            'start_date' => ['nullable', 'required_if:date_mode,manual', 'date'],
            'due_date' => ['nullable', 'required_if:date_mode,manual', 'date', 'after_or_equal:start_date'],
            'relative_start_offset' => ['nullable', 'required_if:date_mode,template_relative', 'integer', 'between:-3650,3650'],
            'relative_due_offset' => ['nullable', 'required_if:date_mode,template_relative', 'integer', 'gte:relative_start_offset', 'between:-3650,3650'],
            'notes' => ['nullable', 'string', 'max:10000'],
        ];
    }

    /** @return array<string, mixed> */
    public function taskData(): array
    {
        return $this->validated();
    }
}
