<?php

namespace App\Http\Requests\EventDay;

use App\Models\Wedding;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePlaylistItemRequest extends FormRequest
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
        return [
            'event_uuid' => ['required', 'uuid', Rule::exists('wedding_events', 'uuid')->where('wedding_id', $this->route('wedding')->id)],
            'title' => ['required', 'string', 'max:200'],
            'artist' => ['nullable', 'string', 'max:200'],
            'link' => ['nullable', 'url', 'max:500'],
            'requested_by' => ['nullable', 'string', 'max:200'],
            'moment' => ['nullable', 'string', 'max:200'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /** @return array<string, mixed> */
    public function itemData(): array
    {
        return [
            'event_uuid' => (string) $this->string('event_uuid'),
            'title' => $this->string('title')->toString(),
            'artist' => $this->nullableString('artist'),
            'link' => $this->nullableString('link'),
            'requested_by' => $this->nullableString('requested_by'),
            'moment' => $this->nullableString('moment'),
            'notes' => $this->nullableString('notes'),
        ];
    }

    private function nullableString(string $field): ?string
    {
        $value = $this->input($field);

        return $value === null || $value === '' ? null : (string) $value;
    }
}
