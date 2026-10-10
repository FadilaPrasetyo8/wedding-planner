<?php

namespace App\Http\Requests\Finance;

use App\Models\BudgetScenario;
use App\Models\Wedding;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreScenarioItemRequest extends FormRequest
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
        /** @var Wedding $wedding */
        $wedding = $this->route('wedding');

        return [
            'name' => ['required', 'string', 'max:150'],
            'amount' => ['required', 'integer', 'min:0'],
            'budget_category_uuid' => ['nullable', 'uuid',
                Rule::exists('budget_categories', 'uuid')->where('wedding_id', $wedding->id)],
            'wedding_event_uuid' => ['nullable', 'uuid',
                Rule::exists('wedding_events', 'uuid')->where('wedding_id', $wedding->id)],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return ['amount.min' => 'Jumlah alokasi tidak boleh negatif.'];
    }

    /** @return array{name: string, amount: int, budget_category_uuid: string|null, wedding_event_uuid: string|null, notes: string|null} */
    public function scenarioItemData(): array
    {
        return [
            'name' => $this->string('name')->toString(),
            'amount' => (int) $this->integer('amount'),
            'budget_category_uuid' => $this->nullableUuid('budget_category_uuid'),
            'wedding_event_uuid' => $this->nullableUuid('wedding_event_uuid'),
            'notes' => $this->input('notes') !== null ? $this->string('notes')->toString() : null,
        ];
    }

    private function nullableUuid(string $key): ?string
    {
        $value = $this->input($key);

        return $value === null || $value === '' ? null : (string) $value;
    }
}
