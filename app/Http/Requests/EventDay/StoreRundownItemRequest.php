<?php

namespace App\Http\Requests\EventDay;

use App\Enums\RundownStatus;
use App\Models\Wedding;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRundownItemRequest extends FormRequest
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
            'pic' => ['nullable', 'string', 'max:200'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i', 'after_or_equal:start_time'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'highlights' => ['nullable', 'string', 'max:500'],
            'status' => ['required', Rule::enum(RundownStatus::class)],
        ];
    }

    /** @return array<string, mixed> */
    public function itemData(): array
    {
        return [
            'event_uuid' => (string) $this->string('event_uuid'),
            'title' => $this->string('title')->toString(),
            'pic' => $this->nullableString('pic'),
            'start_time' => $this->nullableString('start_time'),
            'end_time' => $this->nullableString('end_time'),
            'notes' => $this->nullableString('notes'),
            'highlights' => $this->nullableString('highlights'),
            'status' => $this->string('status')->toString(),
        ];
    }

    private function nullableString(string $field): ?string
    {
        $value = $this->input($field);

        return $value === null || $value === '' ? null : (string) $value;
    }
}
