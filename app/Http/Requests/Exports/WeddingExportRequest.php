<?php

namespace App\Http\Requests\Exports;

use App\Models\Wedding;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class WeddingExportRequest extends FormRequest
{
    public function authorize(): bool
    {
        $wedding = $this->route('wedding');

        return $wedding instanceof Wedding && $this->user()->can('export', $wedding);
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        $wedding = $this->route('wedding');
        $weddingId = $wedding instanceof Wedding ? $wedding->id : 0;
        $routeName = (string) $this->route()?->getName();
        $isChecklist = $routeName === 'weddings.exports.checklist';
        $isPlanning = $routeName === 'weddings.exports.planning';
        $isGuests = $routeName === 'weddings.exports.guests';
        $categoryTable = $isChecklist
            ? 'checklist_categories'
            : ($isPlanning ? 'task_categories' : 'budget_categories');

        return [
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', 'max:50'],
            'category' => [
                'nullable',
                'uuid',
                Rule::exists($categoryTable, 'uuid')->where('wedding_id', $weddingId),
                Rule::when($isGuests, 'prohibited'),
            ],
            'side' => ['nullable', 'uuid', Rule::exists('guest_sides', 'uuid')->where('wedding_id', $weddingId)],
            'group' => ['nullable', 'uuid', Rule::exists('guest_groups', 'uuid')->where('wedding_id', $weddingId)],
            'invitation' => ['nullable', 'string', 'max:50'],
            'rsvp' => ['nullable', 'string', 'max:50'],
            'event' => ['nullable', 'uuid', Rule::exists('wedding_events', 'uuid')->where('wedding_id', $weddingId)],
            'type' => ['nullable', Rule::in(['seserahan', 'administration'])],
            'priority' => ['nullable', 'string', 'max:50'],
            'assignee' => ['nullable', 'integer', Rule::exists('wedding_members', 'id')->where('wedding_id', $weddingId)],
            'overdue' => ['nullable', 'boolean'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'sort' => ['nullable', 'string', 'max:40'],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
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
