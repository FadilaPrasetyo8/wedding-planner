<?php

namespace Database\Factories;

use App\Enums\ChecklistType;
use App\Models\ChecklistStatus;
use App\Models\Wedding;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ChecklistStatus> */
class ChecklistStatusFactory extends Factory
{
    protected $model = ChecklistStatus::class;

    public function definition(): array
    {
        return [
            'wedding_id' => Wedding::factory(),
            'type' => fake()->randomElement(ChecklistType::cases()),
            'name' => fake()->words(2, true),
            'color' => 'slate',
            'is_completed' => false,
            'sort_order' => fake()->numberBetween(1, 100) * 10,
        ];
    }

    public function completed(): static
    {
        return $this->state(fn () => ['is_completed' => true]);
    }
}
