<?php

namespace App\Http\Requests\Finance;

use App\Models\BudgetScenario;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CopyBudgetScenarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var BudgetScenario $scenario */
        $scenario = $this->route('scenario');

        return $this->user()->can('update', $scenario);
    }

    /** @return array<string, list<string|Rule>> */
    public function rules(): array
    {
        /** @var BudgetScenario $scenario */
        $scenario = $this->route('scenario');

        return [
            'name' => ['required', 'string', 'max:100',
                Rule::unique('budget_scenarios', 'name')
                    ->where('wedding_id', $scenario->wedding_id)],
        ];
    }

    public function messages(): array
    {
        return ['name.unique' => 'Nama salinan sudah digunakan di wedding ini.'];
    }

    public function newScenarioName(): string
    {
        return $this->string('name')->toString();
    }
}
