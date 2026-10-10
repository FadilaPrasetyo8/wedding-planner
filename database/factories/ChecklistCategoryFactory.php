<?php

namespace Database\Factories;

use App\Enums\ChecklistType;
use App\Models\ChecklistCategory;
use App\Models\Wedding;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ChecklistCategory> */
class ChecklistCategoryFactory extends Factory
{
    protected $model = ChecklistCategory::class;

    public function definition(): array
    {
        return [
            'wedding_id' => Wedding::factory(),
            'type' => fake()->randomElement(ChecklistType::cases()),
            'name' => fake()->words(2, true),
            'sort_order' => fake()->numberBetween(1, 100) * 10,
        ];
    }
}
