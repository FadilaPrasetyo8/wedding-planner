<?php

namespace App\Http\Requests\Logistics;

use App\Models\Wedding;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreVendorCategoryRequest extends FormRequest
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

        return [
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('vendor_categories', 'name')->where('wedding_id', $wedding->id),
            ],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:10000'],
        ];
    }

    /** @return array{name: string, sort_order: int} */
    public function categoryData(): array
    {
        return [
            'name' => $this->string('name')->toString(),
            'sort_order' => (int) ($this->input('sort_order') ?? 0),
        ];
    }
}
