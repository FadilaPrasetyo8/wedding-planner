<?php

namespace App\Http\Requests\Weddings;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWeddingRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'partner_one_name' => ['required', 'string', 'max:255'],
            'partner_two_name' => ['required', 'string', 'max:255'],
            'wedding_date' => ['required', 'date'],
            'planning_start_date' => ['nullable', 'date', 'before_or_equal:wedding_date'],
            'timezone' => ['required', 'timezone:all'],
            'target_budget' => ['nullable', 'integer', 'min:0'],
            'initial_event_name' => ['required', 'string', 'max:255'],
            'template_id' => [
                'nullable',
                'integer',
                Rule::exists('templates', 'id')->where('is_active', true),
            ],
        ];
    }

    /**
     * @return array{
     *     name: string,
     *     partner_one_name: string,
     *     partner_two_name: string,
     *     wedding_date: string,
     *     planning_start_date: string|null,
     *     timezone: string,
     *     target_budget: int|null,
     *     initial_event_name: string,
     *     template_id: int|null
     * }
     */
    public function weddingData(): array
    {
        return [
            'name' => $this->string('name')->toString(),
            'partner_one_name' => $this->string('partner_one_name')->toString(),
            'partner_two_name' => $this->string('partner_two_name')->toString(),
            'wedding_date' => $this->string('wedding_date')->toString(),
            'planning_start_date' => $this->filled('planning_start_date')
                ? $this->string('planning_start_date')->toString()
                : null,
            'timezone' => $this->string('timezone')->toString(),
            'target_budget' => $this->filled('target_budget')
                ? $this->integer('target_budget')
                : null,
            'initial_event_name' => $this->string('initial_event_name')->toString(),
            'template_id' => $this->filled('template_id')
                ? $this->integer('template_id')
                : null,
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'planning_start_date.before_or_equal' => 'Tanggal mulai perencanaan harus sebelum atau sama dengan tanggal pernikahan.',
            'timezone.timezone' => 'Zona waktu yang dipilih tidak valid.',
            'target_budget.integer' => 'Target anggaran harus berupa nilai rupiah utuh.',
            'target_budget.min' => 'Target anggaran tidak boleh bernilai negatif.',
            'template_id.exists' => 'Template yang dipilih tidak tersedia.',
        ];
    }
}
