<?php

namespace App\Http\Requests\Weddings;

use App\Models\Wedding;
use Illuminate\Foundation\Http\FormRequest;

class UpdateWeddingRequest extends FormRequest
{
    public function authorize(): bool
    {
        $wedding = $this->route('wedding');

        return $wedding instanceof Wedding && $this->user()->can('update', $wedding);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'partner_one_name' => ['required', 'string', 'max:255'],
            'partner_two_name' => ['required', 'string', 'max:255'],
            'wedding_date' => ['required', 'date'],
            'planning_start_date' => ['nullable', 'date', 'before_or_equal:wedding_date'],
            'timezone' => ['required', 'timezone:all'],
            'target_budget' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
