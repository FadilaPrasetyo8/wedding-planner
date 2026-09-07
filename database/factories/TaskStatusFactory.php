<?php

namespace Database\Factories;

use App\Enums\TaskBehavior;
use App\Models\TaskStatus;
use App\Models\Wedding;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<TaskStatus> */
class TaskStatusFactory extends Factory
{
    protected $model = TaskStatus::class;

    public function definition(): array
    {
        return [
            'wedding_id' => Wedding::factory(),
            'name' => fake()->unique()->words(2, true),
            'color' => 'slate',
            'behavior' => TaskBehavior::Todo,
            'sort_order' => 10,
            'is_active' => true,
        ];
    }
}
