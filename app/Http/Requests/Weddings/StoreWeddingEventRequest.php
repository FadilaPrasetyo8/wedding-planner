<?php

namespace App\Http\Requests\Weddings;

use App\Models\Wedding;
use App\Models\WeddingEvent;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWeddingEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        $wedding = $this->route('wedding');

        return $wedding instanceof Wedding
            && $this->user()->can('create', new WeddingEvent(['wedding_id' => $wedding->id]));
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(['engagement', 'akad', 'reception', 'custom'])],
            'event_date' => ['required', 'date'],
            'starts_at' => ['nullable', 'date_format:H:i'],
            'ends_at' => ['nullable', 'date_format:H:i', 'after_or_equal:starts_at'],
            'venue' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'is_primary' => ['required', 'boolean'],
            'confirm_recalculate_relative_tasks' => ['nullable', 'boolean'],
        ];
    }

    /** @return array<string, mixed> */
    public function eventData(): array
    {
        return $this->safe()->except('confirm_recalculate_relative_tasks');
    }
}
