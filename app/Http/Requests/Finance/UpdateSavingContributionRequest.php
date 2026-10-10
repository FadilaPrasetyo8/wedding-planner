<?php

namespace App\Http\Requests\Finance;

use App\Models\SavingContribution;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSavingContributionRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var SavingContribution $contribution */
        $contribution = $this->route('saving_contribution');

        return $this->user()->can('update', $contribution);
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'amount' => ['required', 'integer', 'min:1'],
            'contributed_on' => ['required', 'date'],
            'source' => ['nullable', 'string', 'max:40'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return ['amount.min' => 'Jumlah setoran harus lebih besar dari nol.'];
    }

    /** @return array{amount: int, contributed_on: string, source: string|null, notes: string|null} */
    public function contributionData(): array
    {
        return [
            'amount' => (int) $this->integer('amount'),
            'contributed_on' => (string) $this->string('contributed_on'),
            'source' => $this->input('source') !== null && $this->input('source') !== '' ? $this->string('source')->toString() : null,
            'notes' => $this->input('notes') !== null ? $this->string('notes')->toString() : null,
        ];
    }
}
