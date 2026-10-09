<?php

namespace Database\Factories;

use App\Models\BudgetCategory;
use App\Models\Wedding;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<BudgetCategory> */
class BudgetCategoryFactory extends Factory
{
    protected $model = BudgetCategory::class;

    public function definition(): array
    {
        return [
            'wedding_id' => Wedding::factory(),
            'name' => fake()->randomElement(['Venue', 'Catering', 'Dekorasi', 'Dokumentasi']),
            'color' => fake()->randomElement(['burgundy', 'clay', 'rose', 'sage', 'slate']),
            'sort_order' => 0,
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
