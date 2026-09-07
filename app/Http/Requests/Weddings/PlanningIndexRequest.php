<?php

namespace App\Http\Requests\Weddings;

use App\Enums\TaskPriority;
use App\Models\Wedding;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PlanningIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        $wedding = $this->route('wedding');

        return $wedding instanceof Wedding && $this->user()->can('view', $wedding);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $wedding = $this->route('wedding');
        $weddingId = $wedding instanceof Wedding ? $wedding->id : 0;

        return [
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'uuid', Rule::exists('task_statuses', 'uuid')->where('wedding_id', $weddingId)],
            'category' => ['nullable', 'uuid', Rule::exists('task_categories', 'uuid')->where('wedding_id', $weddingId)],
            'priority' => ['nullable', Rule::enum(TaskPriority::class)],
            'assignee' => ['nullable', 'integer', Rule::exists('wedding_members', 'id')->where('wedding_id', $weddingId)],
            'event' => ['nullable', 'uuid', Rule::exists('wedding_events', 'uuid')->where('wedding_id', $weddingId)],
            'overdue' => ['nullable', 'boolean'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'sort' => ['nullable', Rule::in(['title', 'start_date', 'due_date', 'priority', 'status', 'category', 'updated_at'])],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    /** @return array<string, mixed> */
    public function filters(): array
    {
        $filters = $this->safe()->except('page');

        if (array_key_exists('assignee', $filters)) {
            $filters['assignee'] = $this->integer('assignee');
        }

        if (array_key_exists('overdue', $filters)) {
            $filters['overdue'] = $this->boolean('overdue');
        }

        return $filters;
    }
}
