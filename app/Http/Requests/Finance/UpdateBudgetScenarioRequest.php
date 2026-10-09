<?php

namespace App\Http\Requests\Finance;

use App\Models\BudgetScenario;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBudgetScenarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var BudgetScenario $scenario */
        $scenario = $this->route('scenario');

        return $this->user()->can('update', $scenario);
    }

    /** @return array<string, array<int, string|Rule>> */
    public function rules(): array
    {
        /** @var BudgetScenario $scenario */
        $scenario = $this->route('scenario');

        return [
            'name' => ['required', 'string', 'max:100',
                Rule::unique('budget_scenarios', 'name')
                    ->where('wedding_id', $scenario->wedding_id)
                    ->ignore($scenario->id)],
            'description' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return ['name.unique' => 'Nama skenario sudah digunakan di wedding ini.'];
    }

    /** @return array{name: string, description: string|null, is_active: bool} */
    public function scenarioData(): array
    {
        return [
            'name' => $this->string('name')->toString(),
            'description' => $this->input('description') !== null ? $this->string('description')->toString() : null,
            'is_active' => $this->input('is_active') === null ? true : $this->boolean('is_active'),
        ];
    }
}
