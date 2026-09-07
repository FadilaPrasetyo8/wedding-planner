<?php

namespace App\Http\Requests\Weddings;

use App\Enums\TaskDateMode;
use App\Models\Wedding;
use App\Models\WeddingEvent;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateWeddingEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        $wedding = $this->route('wedding');
        $event = $this->route('event');

        return $wedding instanceof Wedding
            && $event instanceof WeddingEvent
            && $this->user()->can('update', $event);
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
            'confirm_recalculate_relative_tasks' => ['nullable', 'accepted'],
        ];
    }

    /** @return array<string, mixed> */
    public function eventData(): array
    {
        return $this->safe()->except('confirm_recalculate_relative_tasks');
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $event = $this->route('event');
            if (! $event instanceof WeddingEvent) {
                return;
            }

            if ($event->is_primary && ! $this->boolean('is_primary')) {
                $validator->errors()->add('is_primary', 'Pilih acara utama pengganti sebelum menonaktifkan acara ini.');
            }

            if (! $event->is_primary && $this->boolean('is_primary')) {
                $hasRelativeTasks = $event->wedding->tasks()
                    ->where('date_mode', TaskDateMode::TemplateRelative)
                    ->exists();
                if ($hasRelativeTasks && ! $this->boolean('confirm_recalculate_relative_tasks')) {
                    $validator->errors()->add(
                        'confirm_recalculate_relative_tasks',
                        'Konfirmasikan perhitungan ulang tanggal tugas relatif.',
                    );
                }
            }
        });
    }
}
