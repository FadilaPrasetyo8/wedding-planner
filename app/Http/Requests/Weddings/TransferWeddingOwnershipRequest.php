<?php

namespace App\Http\Requests\Weddings;

use App\Models\Wedding;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TransferWeddingOwnershipRequest extends FormRequest
{
    public function authorize(): bool
    {
        $wedding = $this->route('wedding');

        return $wedding instanceof Wedding && $this->user()->can('manageMembers', $wedding);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $wedding = $this->route('wedding');
        $weddingId = $wedding instanceof Wedding ? $wedding->id : 0;

        return [
            'membership_id' => [
                'required',
                'integer',
                Rule::exists('wedding_members', 'id')->where(fn ($query) => $query
                    ->where('wedding_id', $weddingId)
                    ->whereNull('left_at')
                    ->where('user_id', '!=', $this->user()->id)),
            ],
        ];
    }
}
