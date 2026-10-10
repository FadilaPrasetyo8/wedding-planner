<?php

namespace Database\Factories;

use App\Models\BudgetScenario;
use App\Models\ScenarioItem;
use App\Models\Wedding;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ScenarioItem> */
class ScenarioItemFactory extends Factory
{
    protected $model = ScenarioItem::class;

    public function definition(): array
    {
        return [
            'wedding_id' => Wedding::factory(),
            'budget_scenario_id' => BudgetScenario::factory(),
            'budget_category_id' => null,
            'wedding_event_id' => null,
            'name' => fake()->words(3, true),
            'amount' => fake()->numberBetween(500_000, 20_000_000),
            'notes' => fake()->optional()->sentence(),
            'sort_order' => 0,
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
