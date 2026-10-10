<?php

namespace App\Http\Requests\Logistics;

use App\Enums\GuestInvitationStatus;
use App\Enums\GuestRsvpStatus;
use App\Models\Wedding;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreGuestRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:200'],
            'guest_side_uuid' => ['nullable', 'uuid', Rule::exists('guest_sides', 'uuid')->where('wedding_id', $wedding->id)],
            'guest_group_uuid' => ['nullable', 'uuid', Rule::exists('guest_groups', 'uuid')->where('wedding_id', $wedding->id)],
            'phone' => ['nullable', 'string', 'max:30'],
            'invitation_count' => ['required', 'integer', 'min:0', 'max:100'],
            'pax_count' => ['required', 'integer', 'min:0', 'max:100'],
            'invitation_status' => ['required', Rule::enum(GuestInvitationStatus::class)],
            'rsvp_status' => ['nullable', Rule::enum(GuestRsvpStatus::class)],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /** @return array<string, mixed> */
    public function guestData(): array
    {
        return [
            'name' => $this->string('name')->toString(),
            'guest_side_uuid' => $this->nullableString('guest_side_uuid'),
            'guest_group_uuid' => $this->nullableString('guest_group_uuid'),
            'phone' => $this->input('phone') !== null && $this->input('phone') !== '' ? $this->string('phone')->toString() : null,
            'invitation_count' => (int) $this->integer('invitation_count'),
            'pax_count' => (int) $this->integer('pax_count'),
            'invitation_status' => $this->string('invitation_status')->toString(),
            'rsvp_status' => $this->input('rsvp_status') !== null && $this->input('rsvp_status') !== '' ? $this->string('rsvp_status')->toString() : null,
            'notes' => $this->input('notes') !== null ? $this->string('notes')->toString() : null,
        ];
    }

    private function nullableString(string $field): ?string
    {
        $value = $this->input($field);

        return $value === null || $value === '' ? null : (string) $value;
    }
}
