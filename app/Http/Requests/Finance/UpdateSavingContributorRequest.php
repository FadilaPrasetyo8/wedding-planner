<?php

namespace App\Http\Requests\Finance;

use App\Models\SavingContributor;
use App\Models\Wedding;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSavingContributorRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var SavingContributor $contributor */
        $contributor = $this->route('saving_contributor');

        return $this->user()->can('update', $contributor);
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
            'is_active' => ['boolean'],
        ];
    }

    /** @return array{label: string, wedding_member_id: int|null, is_active: bool} */
    public function contributorData(): array
    {
        $membershipId = $this->input('membership_id');

        return [
            'label' => $this->string('label')->toString(),
            'wedding_member_id' => $membershipId !== null && $membershipId !== '' ? (int) $this->integer('membership_id') : null,
            'is_active' => $this->input('is_active') === null ? true : $this->boolean('is_active'),
        ];
    }
}
