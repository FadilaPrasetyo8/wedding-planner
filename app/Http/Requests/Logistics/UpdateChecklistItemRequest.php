<?php

namespace App\Http\Requests\Logistics;

use App\Enums\ChecklistType;
use App\Models\ChecklistItem;
use App\Models\Wedding;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateChecklistItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Wedding $wedding */
        $wedding = $this->route('wedding');

        return $this->user()->can('editOperationalData', $wedding);
    }

    /** @return array<string, list<string|Rule>> */
    public function rules(): array
    {
        /** @var Wedding $wedding */
        $wedding = $this->route('wedding');
        /** @var ChecklistItem $item */
        $item = $this->route('checklist_item');
        $type = $item->type->value;
        $isSeserahan = $type === ChecklistType::Seserahan->value;

        return [
            'name' => ['required', 'string', 'max:200'],
            'checklist_group_uuid' => ['nullable', 'uuid', Rule::exists('checklist_groups', 'uuid')->where('wedding_id', $wedding->id)->where('type', $type)],
            'checklist_category_uuid' => ['nullable', 'uuid', Rule::exists('checklist_categories', 'uuid')->where('wedding_id', $wedding->id)->where('type', $type)],
            'checklist_status_uuid' => ['nullable', 'uuid', Rule::exists('checklist_statuses', 'uuid')->where('wedding_id', $wedding->id)->where('type', $type)],
            'assignee_member_id' => ['nullable', 'integer', Rule::exists('wedding_members', 'id')->where('wedding_id', $wedding->id)->whereNull('left_at')],
            'notes' => ['nullable', 'string', 'max:2000'],
            'quantity' => ['required', 'integer', 'min:1', 'max:1000'],
            'estimated_cost' => ['nullable', 'integer', 'min:0'],
            'actual_cost' => ['nullable', 'integer', 'min:0'],
            'deadline' => ['nullable', 'date'],
            'purchase_url' => [$isSeserahan ? 'nullable' : 'prohibited', 'url', 'max:500'],
        ];
    }

    /** @return array<string, mixed> */
    public function itemData(): array
    {
        return [
            'name' => $this->string('name')->toString(),
            'checklist_group_uuid' => $this->nullableString('checklist_group_uuid'),
            'checklist_category_uuid' => $this->nullableString('checklist_category_uuid'),
            'checklist_status_uuid' => $this->nullableString('checklist_status_uuid'),
            'assignee_member_id' => $this->nullableId('assignee_member_id'),
            'notes' => $this->input('notes') !== null ? $this->string('notes')->toString() : null,
            'quantity' => (int) $this->integer('quantity'),
            'estimated_cost' => $this->nullableInt('estimated_cost'),
            'actual_cost' => $this->nullableInt('actual_cost'),
            'deadline' => $this->filled('deadline') ? $this->string('deadline')->toString() : null,
            'purchase_url' => $this->input('purchase_url') !== null && $this->input('purchase_url') !== '' ? $this->string('purchase_url')->toString() : null,
        ];
    }

    private function nullableId(string $field): ?int
    {
        $value = $this->input($field);

        return $value === null || $value === '' ? null : (int) $value;
    }

    private function nullableInt(string $field): ?int
    {
        $value = $this->input($field);

        return $value === null || $value === '' ? null : (int) $value;
    }

    private function nullableString(string $field): ?string
    {
        $value = $this->input($field);

        return $value === null || $value === '' ? null : (string) $value;
    }
}
