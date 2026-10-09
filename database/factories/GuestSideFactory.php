<?php

namespace Database\Factories;

use App\Models\GuestSide;
use App\Models\Wedding;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<GuestSide> */
class GuestSideFactory extends Factory
{
    protected $model = GuestSide::class;

    public function definition(): array
    {
        return [
            'wedding_id' => Wedding::factory(),
            'name' => fake()->words(2, true),
            'sort_order' => fake()->numberBetween(1, 100) * 10,
        ];
    }
}
