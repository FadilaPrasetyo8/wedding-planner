<?php

namespace App\Http\Requests\CustomFields;

use App\Enums\CustomFieldType;
use App\Models\CustomFieldDefinition;
use App\Models\Wedding;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCustomFieldDefinitionRequest extends FormRequest
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
        $definition = $this->route('custom_field_definition');
        $selectTypes = [CustomFieldType::Select->value, CustomFieldType::MultiSelect->value];

        return [
            'label' => ['required', 'string', 'max:100', Rule::unique('custom_field_definitions', 'label')->where('wedding_id', $wedding->id)->where('module', $definition->module->value)->ignore($definition->id)],
            'is_required' => ['nullable', 'boolean'],
            'options' => ['required_if:field_type,'.implode(',', $selectTypes), 'array', 'max:50'],
            'options.*' => ['required', 'string', 'max:100', 'distinct'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:32767'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Type changes are rejected while values exist (D-027); module is immutable.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'field_type.not_in' => 'Tipe field tidak dapat diubah selama masih memiliki nilai.',
        ];
    }

    /** @return array{label: string, is_required: bool, options: list<string>|null, sort_order: int, is_active: bool} */
    public function definitionData(): array
    {
        $definition = $this->route('custom_field_definition');
        $type = $definition->field_type;
        $options = $this->input('options');

        return [
            'label' => $this->string('label')->toString(),
            'is_required' => (bool) $this->boolean('is_required'),
            'options' => $this->normalizedOptions($type, $options),
            'sort_order' => (int) $this->integer('sort_order', 0),
            'is_active' => (bool) $this->boolean('is_active', true),
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
