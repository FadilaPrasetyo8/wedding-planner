<?php

namespace Database\Factories;

use App\Models\TaskCategory;
use App\Models\Wedding;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<TaskCategory> */
class TaskCategoryFactory extends Factory
{
    protected $model = TaskCategory::class;

    public function definition(): array
    {
        return [
            'wedding_id' => Wedding::factory(),
            'name' => fake()->unique()->words(2, true),
            'color' => 'clay',
            'sort_order' => 10,
            'is_active' => true,
        ];
    }
}
