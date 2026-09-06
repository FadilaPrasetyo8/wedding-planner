<?php

namespace App\Http\Requests\Weddings;

use App\Models\Wedding;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DeleteWeddingRequest extends FormRequest
{
    public function authorize(): bool
    {
        $wedding = $this->route('wedding');

        return $wedding instanceof Wedding && $this->user()->can('update', $wedding);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $wedding = $this->route('wedding');
        $weddingName = $wedding instanceof Wedding ? $wedding->name : '';

        return [
            'confirmation' => ['required', 'string', Rule::in([$weddingName])],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return ['confirmation.in' => 'Ketik nama ruang pernikahan dengan tepat untuk melanjutkan.'];
    }
}
