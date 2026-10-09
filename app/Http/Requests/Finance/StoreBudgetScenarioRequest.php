<?php

namespace App\Http\Requests\Finance;

use App\Models\Wedding;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBudgetScenarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Wedding $wedding */
        $wedding = $this->route('wedding');

        return $this->user()->can('editOperationalData', $wedding);
    }

    /** @return array<string, array<int, string|Rule>> */
    public function rules(): array
    {
        /** @var Wedding $wedding */
        $wedding = $this->route('wedding');

        return [
            'name' => ['required', 'string', 'max:100',
                Rule::unique('budget_scenarios', 'name')->where('wedding_id', $wedding->id)],
            'description' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return ['name.unique' => 'Nama skenario sudah digunakan di wedding ini.'];
    }

    /** @return array{name: string, description: string|null} */
    public function scenarioData(): array
    {
        return [
            'name' => $this->string('name')->toString(),
            'description' => $this->input('description') !== null ? $this->string('description')->toString() : null,
        ];
    }
}
