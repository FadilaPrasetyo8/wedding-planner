<?php

namespace Database\Factories;

use App\Models\BudgetScenario;
use App\Models\Wedding;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<BudgetScenario> */
class BudgetScenarioFactory extends Factory
{
    protected $model = BudgetScenario::class;

    public function definition(): array
    {
        return [
            'wedding_id' => Wedding::factory(),
            'name' => fake()->randomElement(['Hemat', 'Ideal', 'Premium']),
            'description' => fake()->optional()->sentence(),
            'sort_order' => 0,
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
