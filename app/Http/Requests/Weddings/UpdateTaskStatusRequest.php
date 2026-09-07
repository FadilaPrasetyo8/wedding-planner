<?php

namespace App\Http\Requests\Weddings;

use App\Models\TaskStatus;
use App\Models\Wedding;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTaskStatusRequest extends FormRequest
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
        $status = $this->route('task_status');

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('task_statuses')->where('wedding_id', $wedding instanceof Wedding ? $wedding->id : 0)->ignore($status instanceof TaskStatus ? $status->id : null)],
            'color' => ['required', Rule::in(['burgundy', 'clay', 'rose', 'sage', 'slate'])],
            'sort_order' => ['required', 'integer', 'min:0'],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
