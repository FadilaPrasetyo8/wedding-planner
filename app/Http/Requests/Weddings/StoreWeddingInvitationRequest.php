<?php

namespace App\Http\Requests\Weddings;

use App\Enums\WeddingMemberRole;
use App\Models\Wedding;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWeddingInvitationRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge(['email' => mb_strtolower(trim((string) $this->input('email')))]);
    }

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
            'email' => [
                'required', 'email:rfc', 'max:255',
                Rule::unique('wedding_invitations', 'email')->where(fn ($query) => $query
                    ->where('wedding_id', $weddingId)
                    ->whereNull('accepted_at')
                    ->whereNull('declined_at')
                    ->whereNull('cancelled_at')),
            ],
            'role' => ['required', Rule::enum(WeddingMemberRole::class)->only([
                WeddingMemberRole::Editor,
                WeddingMemberRole::Viewer,
            ])],
        ];
    }

    /** @return array{email: string, role: string} */
    public function invitationData(): array
    {
        return [
            'email' => $this->string('email')->toString(),
            'role' => $this->string('role')->toString(),
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'email.unique' => 'Undangan aktif untuk alamat email ini sudah ada.',
            'role.enum' => 'Undangan hanya dapat memberikan peran editor atau hanya lihat.',
        ];
    }
}
