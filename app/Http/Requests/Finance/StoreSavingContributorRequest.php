<?php

namespace App\Http\Requests\Finance;

use App\Models\Wedding;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSavingContributorRequest extends FormRequest
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
            'label' => ['required', 'string', 'max:100'],
            'membership_id' => ['nullable', 'integer',
                Rule::exists('wedding_members', 'id')->where('wedding_id', $wedding->id)->whereNull('left_at')],
        ];
    }

    /** @return array{label: string, wedding_member_id: int|null} */
    public function contributorData(): array
    {
        $membershipId = $this->input('membership_id');

        return [
            'label' => $this->string('label')->toString(),
            'wedding_member_id' => $membershipId !== null && $membershipId !== '' ? (int) $this->integer('membership_id') : null,
        ];
    }
}
