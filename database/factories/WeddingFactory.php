<?php

namespace Database\Factories;

use App\Models\Wedding;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Wedding> */
class WeddingFactory extends Factory
{
    protected $model = Wedding::class;

    public function definition(): array
    {
        $date = fake()->dateTimeBetween('+3 months', '+2 years');

        return ['name' => 'Pernikahan '.fake()->lastName(), 'partner_one_name' => fake()->firstName(), 'partner_two_name' => fake()->firstName(), 'wedding_date' => $date, 'planning_start_date' => now(), 'timezone' => 'Asia/Jakarta', 'currency' => 'IDR', 'target_budget' => fake()->numberBetween(10_000_000, 200_000_000), 'status' => 'planning'];
    }
}
