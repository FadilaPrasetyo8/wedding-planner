<?php

namespace App\Http\Requests\Finance;

use App\Models\Wedding;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBudgetItemRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:150'],
            'estimated_amount' => ['required', 'integer', 'min:0'],
            'final_amount' => ['nullable', 'integer', 'min:0'],
            'budget_category_uuid' => ['nullable', 'uuid',
                Rule::exists('budget_categories', 'uuid')->where('wedding_id', $wedding->id)],
            'wedding_event_uuid' => ['nullable', 'uuid',
                Rule::exists('wedding_events', 'uuid')->where('wedding_id', $wedding->id)],
            'vendor_uuid' => ['nullable', 'uuid',
                Rule::exists('vendors', 'uuid')->where('wedding_id', $wedding->id)],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'estimated_amount.min' => 'Estimasi biaya tidak boleh negatif.',
            'final_amount.min' => 'Jumlah final tidak boleh negatif.',
        ];
    }

    /** @return array<string, mixed> */
    public function budgetItemData(): array
    {
        return [
            'name' => $this->string('name')->toString(),
            'estimated_amount' => (int) $this->integer('estimated_amount'),
            'final_amount' => $this->input('final_amount') !== null && $this->input('final_amount') !== '' ? (int) $this->integer('final_amount') : null,
            'budget_category_uuid' => $this->nullableUuid('budget_category_uuid'),
            'wedding_event_uuid' => $this->nullableUuid('wedding_event_uuid'),
            'vendor_uuid' => $this->nullableUuid('vendor_uuid'),
            'notes' => $this->input('notes') !== null ? $this->string('notes')->toString() : null,
        ];
    }

    private function nullableUuid(string $key): ?string
    {
        $value = $this->input($key);

        return $value === null || $value === '' ? null : (string) $value;
    }
}
