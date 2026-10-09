<?php

namespace Database\Factories;

use App\Enums\BudgetItemStatus;
use App\Models\BudgetItem;
use App\Models\Wedding;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<BudgetItem> */
class BudgetItemFactory extends Factory
{
    protected $model = BudgetItem::class;

    public function definition(): array
    {
        return [
            'wedding_id' => Wedding::factory(),
            'budget_category_id' => null,
            'wedding_event_id' => null,
            'name' => fake()->words(3, true),
            'estimated_amount' => fake()->numberBetween(500_000, 20_000_000),
            'final_amount' => null,
            'status' => BudgetItemStatus::Draft,
            'notes' => fake()->optional()->sentence(),
            'source_scenario_name' => null,
            'source_item_name' => null,
            'application_batch_id' => null,
        ];
    }

    public function committed(): static
    {
        return $this->state(fn () => [
            'status' => BudgetItemStatus::Committed,
            'final_amount' => fake()->numberBetween(500_000, 20_000_000),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn () => ['status' => BudgetItemStatus::Cancelled]);
    }
}
