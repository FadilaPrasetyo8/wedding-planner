<?php

namespace App\Http\Requests\CustomFields;

use App\Enums\CustomFieldModule;
use App\Enums\CustomFieldType;
use App\Models\Wedding;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCustomFieldDefinitionRequest extends FormRequest
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
        $selectTypes = [CustomFieldType::Select->value, CustomFieldType::MultiSelect->value];

        return [
            'module' => ['required', 'string', Rule::enum(CustomFieldModule::class)],
            'field_type' => ['required', 'string', Rule::enum(CustomFieldType::class)],
            'label' => ['required', 'string', 'max:100', Rule::unique('custom_field_definitions', 'label')->where('wedding_id', $wedding->id)->where('module', $this->string('module'))],
            'is_required' => ['nullable', 'boolean'],
            'options' => ['required_if:field_type,'.implode(',', $selectTypes), 'array', 'max:50'],
            'options.*' => ['required', 'string', 'max:100', 'distinct'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:32767'],
        ];
    }

    /** @return array{module: CustomFieldModule, field_type: CustomFieldType, label: string, is_required: bool, options: list<string>|null, sort_order: int} */
    public function definitionData(): array
    {
        $type = CustomFieldType::from($this->string('field_type')->toString());
        $options = $this->input('options');

        return [
            'module' => CustomFieldModule::from($this->string('module')->toString()),
            'field_type' => $type,
            'label' => $this->string('label')->toString(),
            'is_required' => (bool) $this->boolean('is_required'),
            'options' => $this->normalizedOptions($type, $options),
            'sort_order' => (int) $this->integer('sort_order', 0),
        ];
    }

    /** @param  list<mixed>|null  $options */
    private function normalizedOptions(CustomFieldType $type, ?array $options): ?array
    {
        if ($type === CustomFieldType::Select || $type === CustomFieldType::MultiSelect) {
            $clean = array_values(array_filter(array_map('strval', $options ?? []), fn (string $option): bool => trim($option) !== ''));

            return $clean === [] ? null : $clean;
        }

        return null;
    }
}
