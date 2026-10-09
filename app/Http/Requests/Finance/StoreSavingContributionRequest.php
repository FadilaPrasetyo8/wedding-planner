<?php

namespace App\Http\Requests\Finance;

use App\Models\Wedding;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSavingContributionRequest extends FormRequest
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
            'saving_goal_uuid' => ['required', 'uuid',
                Rule::exists('saving_goals', 'uuid')->where('wedding_id', $wedding->id)],
            'saving_contributor_uuid' => ['required', 'uuid',
                Rule::exists('saving_contributors', 'uuid')->where('wedding_id', $wedding->id)],
            'amount' => ['required', 'integer', 'min:1'],
            'contributed_on' => ['required', 'date'],
            'source' => ['nullable', 'string', 'max:40'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'amount.min' => 'Jumlah setoran harus lebih besar dari nol.',
            'amount.required' => 'Jumlah setoran wajib diisi.',
        ];
    }

    /** @return array{saving_goal_uuid: string, saving_contributor_uuid: string, amount: int, contributed_on: string, source: string|null, notes: string|null} */
    public function contributionData(): array
    {
        return [
            'saving_goal_uuid' => (string) $this->string('saving_goal_uuid'),
            'saving_contributor_uuid' => (string) $this->string('saving_contributor_uuid'),
            'amount' => (int) $this->integer('amount'),
            'contributed_on' => (string) $this->string('contributed_on'),
            'source' => $this->input('source') !== null && $this->input('source') !== '' ? $this->string('source')->toString() : null,
            'notes' => $this->input('notes') !== null ? $this->string('notes')->toString() : null,
        ];
    }
}
