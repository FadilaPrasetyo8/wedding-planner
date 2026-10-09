<?php

namespace Database\Factories;

use App\Models\SavingContributor;
use App\Models\Wedding;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<SavingContributor> */
class SavingContributorFactory extends Factory
{
    protected $model = SavingContributor::class;

    public function definition(): array
    {
        return [
            'wedding_id' => Wedding::factory(),
            'wedding_member_id' => null,
            'label' => fake()->name(),
            'sort_order' => 0,
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
