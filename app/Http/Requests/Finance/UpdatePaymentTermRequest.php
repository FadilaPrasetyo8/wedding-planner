<?php

namespace App\Http\Requests\Finance;

use App\Models\PaymentTerm;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePaymentTermRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var PaymentTerm $term */
        $term = $this->route('payment_term');

        return $this->user()->can('update', $term);
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'planned_amount' => ['required', 'integer', 'min:0'],
            'due_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return ['planned_amount.min' => 'Jumlah rencana tidak boleh negatif.'];
    }

    /** @return array{name: string, planned_amount: int, due_date: string|null, notes: string|null} */
    public function paymentTermData(): array
    {
        $dueDate = $this->input('due_date');

        return [
            'name' => $this->string('name')->toString(),
            'planned_amount' => (int) $this->integer('planned_amount'),
            'due_date' => $dueDate === null || $dueDate === '' ? null : (string) $dueDate,
            'notes' => $this->input('notes') !== null ? $this->string('notes')->toString() : null,
        ];
    }
}
