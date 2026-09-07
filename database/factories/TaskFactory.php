<?php

namespace Database\Factories;

use App\Enums\TaskDateMode;
use App\Enums\TaskPriority;
use App\Models\Task;
use App\Models\TaskCategory;
use App\Models\TaskStatus;
use App\Models\Wedding;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Task> */
class TaskFactory extends Factory
{
    protected $model = Task::class;

    public function definition(): array
    {
        $startDate = fake()->dateTimeBetween('now', '+3 months');

        return [
            'wedding_id' => Wedding::factory(),
            'title' => fake()->sentence(4),
            'description' => fake()->optional()->paragraph(),
            'task_category_id' => TaskCategory::factory(),
            'task_status_id' => TaskStatus::factory(),
            'priority' => TaskPriority::Medium,
            'assignee_membership_id' => null,
            'wedding_event_id' => null,
            'date_mode' => TaskDateMode::Manual,
            'start_date' => $startDate,
            'due_date' => (clone $startDate)->modify('+7 days'),
            'relative_start_offset' => null,
            'relative_due_offset' => null,
            'completed_at' => null,
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
