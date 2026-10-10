<?php

namespace App\Http\Requests\Logistics;

use App\Enums\ChecklistType;
use App\Models\Wedding;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreChecklistReferenceRequest extends FormRequest
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
        $type = $this->string('type')->toString();
        /** @var string $routeType */
        $routeType = $this->route('type');

        return [
            'type' => ['required', Rule::enum(ChecklistType::class), Rule::in([$routeType])],
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique($this->referenceTable(), 'name')
                    ->where('wedding_id', $wedding->id)
                    ->where('type', $type),
            ],
            'color' => ['nullable', 'string', 'max:20'],
            'is_completed' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:10000'],
        ];
    }

    /** @return array<string, mixed> */
    public function referenceData(): array
    {
        $data = [
            'type' => $this->string('type')->toString(),
            'name' => $this->string('name')->toString(),
            'sort_order' => (int) ($this->input('sort_order') ?? 0),
        ];

        if ($this->referenceTable() === 'checklist_statuses') {
            $data['color'] = $this->input('color') ?? 'slate';
            $data['is_completed'] = $this->boolean('is_completed');
        }

        return $data;
    }

    private function referenceTable(): string
    {
        return $this->route('reference') === 'statuses' ? 'checklist_statuses' : 'checklist_categories';
    }
}
