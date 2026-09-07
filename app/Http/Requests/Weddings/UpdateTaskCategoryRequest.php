<?php

namespace App\Http\Requests\Weddings;

use App\Models\TaskCategory;
use App\Models\Wedding;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTaskCategoryRequest extends FormRequest
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
        $category = $this->route('task_category');

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('task_categories')->where('wedding_id', $wedding instanceof Wedding ? $wedding->id : 0)->ignore($category instanceof TaskCategory ? $category->id : null)],
            'color' => ['required', Rule::in(['burgundy', 'clay', 'rose', 'sage', 'slate'])],
            'sort_order' => ['required', 'integer', 'min:0'],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
