<?php

namespace App\Http\Requests\Finance;

use App\Models\SavingGoal;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSavingGoalRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var SavingGoal $goal */
        $goal = $this->route('saving_goal');

        return $this->user()->can('update', $goal);
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'target_amount' => ['required', 'integer', 'min:0'],
            'target_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return ['target_amount.min' => 'Target tabungan tidak boleh negatif.'];
    }

    /** @return array{name: string, target_amount: int, target_date: string|null, notes: string|null, is_active: bool} */
    public function goalData(): array
    {
        $targetDate = $this->input('target_date');

        return [
            'name' => $this->string('name')->toString(),
            'target_amount' => (int) $this->integer('target_amount'),
            'target_date' => $targetDate === null || $targetDate === '' ? null : (string) $targetDate,
            'notes' => $this->input('notes') !== null ? $this->string('notes')->toString() : null,
            'is_active' => $this->input('is_active') === null ? true : $this->boolean('is_active'),
        ];
    }
}
