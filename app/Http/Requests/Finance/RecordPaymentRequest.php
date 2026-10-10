<?php

namespace App\Http\Requests\Finance;

use App\Models\PaymentTerm;
use Illuminate\Foundation\Http\FormRequest;

class RecordPaymentRequest extends FormRequest
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
            'actual_paid_amount' => ['required', 'integer', 'min:0'],
            'paid_at' => ['nullable', 'date'],
        ];
    }

    public function messages(): array
    {
        return ['actual_paid_amount.min' => 'Jumlah bayar aktual tidak boleh negatif.'];
    }

    public function actualPaidAmount(): int
    {
        return (int) $this->integer('actual_paid_amount');
    }

    public function paidAt(): ?string
    {
        $paidAt = $this->input('paid_at');

        return $paidAt === null || $paidAt === '' ? null : (string) $paidAt;
    }
}
