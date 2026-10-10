<?php

namespace Database\Factories;

use App\Models\SavingGoal;
use App\Models\Wedding;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<SavingGoal> */
class SavingGoalFactory extends Factory
{
    protected $model = SavingGoal::class;

    public function definition(): array
    {
        return [
            'wedding_id' => Wedding::factory(),
            'name' => fake()->randomElement(['Dana Pernikahan', 'Honeymoon', 'Cincin']),
            'target_amount' => fake()->numberBetween(5_000_000, 100_000_000),
            'target_date' => fake()->optional()->dateTimeBetween('now', '+2 years'),
            'is_active' => true,
            'notes' => fake()->optional()->sentence(),
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
