<?php

namespace App\Http\Requests\Weddings;

use App\Enums\WeddingMemberRole;
use App\Models\Wedding;
use App\Models\WeddingMember;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateWeddingMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        $wedding = $this->route('wedding');
        $membership = $this->route('membership');

        return $wedding instanceof Wedding
            && $membership instanceof WeddingMember
            && $membership->wedding_id === $wedding->id
            && $this->user()->can('manageMembers', $wedding);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'role' => ['required', Rule::enum(WeddingMemberRole::class)->only([
                WeddingMemberRole::Editor,
                WeddingMemberRole::Viewer,
            ])],
        ];
    }
}
