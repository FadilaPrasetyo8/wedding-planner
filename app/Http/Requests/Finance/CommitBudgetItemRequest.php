<?php

namespace App\Http\Requests\Finance;

use App\Models\BudgetItem;
use Illuminate\Foundation\Http\FormRequest;

class CommitBudgetItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var BudgetItem $item */
        $item = $this->route('budget_item');

        return $this->user()->can('commit', $item);
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'final_amount' => ['required', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'final_amount.required' => 'Jumlah final wajib diisi untuk mengomit item.',
            'final_amount.min' => 'Jumlah final tidak boleh negatif.',
        ];
    }

    public function finalAmount(): int
    {
        return (int) $this->integer('final_amount');
    }
}
