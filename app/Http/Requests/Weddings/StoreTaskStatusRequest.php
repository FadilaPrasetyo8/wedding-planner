<?php

namespace App\Http\Requests\Weddings;

use App\Enums\TaskBehavior;
use App\Models\Wedding;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTaskStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        $wedding = $this->route('wedding');

        return $wedding instanceof Wedding && $this->user()->can('editOperationalData', $wedding);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $wedding = $this->route('wedding');

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('task_statuses')->where('wedding_id', $wedding instanceof Wedding ? $wedding->id : 0)],
            'color' => ['required', Rule::in(['burgundy', 'clay', 'rose', 'sage', 'slate'])],
            'behavior' => [
                'required',
                Rule::enum(TaskBehavior::class),
                Rule::unique('task_statuses')->where('wedding_id', $wedding instanceof Wedding ? $wedding->id : 0),
            ],
            'sort_order' => ['required', 'integer', 'min:0'],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
