<?php

namespace Database\Factories;

use App\Models\SavingContribution;
use App\Models\SavingContributor;
use App\Models\SavingGoal;
use App\Models\Wedding;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<SavingContribution> */
class SavingContributionFactory extends Factory
{
    protected $model = SavingContribution::class;

    public function definition(): array
    {
        return [
            'wedding_id' => Wedding::factory(),
            'saving_goal_id' => SavingGoal::factory(),
            'saving_contributor_id' => SavingContributor::factory(),
            'amount' => fake()->numberBetween(100_000, 10_000_000),
            'contributed_on' => fake()->optional()->dateTimeBetween('-1 year', 'now'),
            'source' => fake()->optional()->randomElement(['transfer', 'cash', 'other']),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
