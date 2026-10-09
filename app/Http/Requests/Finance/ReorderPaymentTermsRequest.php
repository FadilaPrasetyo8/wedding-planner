<?php

namespace App\Http\Requests\Finance;

use App\Models\BudgetItem;
use Illuminate\Foundation\Http\FormRequest;

class ReorderPaymentTermsRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var BudgetItem $item */
        $item = $this->route('budget_item');

        return $this->user()->can('update', $item);
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'term_uuids' => ['required', 'array', 'min:1'],
            'term_uuids.*' => ['required', 'uuid'],
        ];
    }

    /** @return list<string> */
    public function termUuids(): array
    {
        return array_values($this->array('term_uuids'));
    }
}
