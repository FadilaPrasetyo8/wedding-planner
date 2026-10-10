<?php

namespace App\Http\Requests\Finance;

use App\Models\BudgetScenario;
use Illuminate\Foundation\Http\FormRequest;

class ApplyBudgetScenarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var BudgetScenario $scenario */
        $scenario = $this->route('scenario');

        return $this->user()->can('update', $scenario);
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'confirm' => ['nullable', 'boolean'],
        ];
    }

    public function confirmed(): bool
    {
        return $this->boolean('confirm');
    }
}
