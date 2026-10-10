<?php

namespace Database\Factories;

use App\Enums\CustomFieldModule;
use App\Enums\CustomFieldType;
use App\Models\CustomFieldDefinition;
use App\Models\Wedding;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<CustomFieldDefinition> */
class CustomFieldDefinitionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'wedding_id' => Wedding::factory(),
            'module' => CustomFieldModule::Guest->value,
            'field_type' => CustomFieldType::Text->value,
            'label' => 'Nomor meja',
            'is_required' => false,
            'options' => null,
            'sort_order' => 0,
            'is_active' => true,
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (CustomFieldDefinition $definition): void {
            if ($definition->field_key === null) {
                $definition->forceFill(['field_key' => CustomFieldDefinition::buildFieldKey($definition->label).'-'.$definition->id])->save();
            }
        });
    }
}
