<?php

namespace App\Http\Requests\Finance;

use App\Models\Wedding;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSavingGoalRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:100'],
            'target_amount' => ['required', 'integer', 'min:0'],
            'target_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return ['target_amount.min' => 'Target tabungan tidak boleh negatif.'];
    }

    /** @return array{name: string, target_amount: int, target_date: string|null, notes: string|null} */
    public function goalData(): array
    {
        $targetDate = $this->input('target_date');

        return [
            'name' => $this->string('name')->toString(),
            'target_amount' => (int) $this->integer('target_amount'),
            'target_date' => $targetDate === null || $targetDate === '' ? null : (string) $targetDate,
            'notes' => $this->input('notes') !== null ? $this->string('notes')->toString() : null,
        ];
    }
}
