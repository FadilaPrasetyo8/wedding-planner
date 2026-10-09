<?php

namespace Database\Factories;

use App\Enums\ChecklistType;
use App\Models\ChecklistItem;
use App\Models\Wedding;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ChecklistItem> */
class ChecklistItemFactory extends Factory
{
    protected $model = ChecklistItem::class;

    public function definition(): array
    {
        return [
            'wedding_id' => Wedding::factory(),
            'type' => fake()->randomElement(ChecklistType::cases()),
            'checklist_group_id' => null,
            'checklist_category_id' => null,
            'checklist_status_id' => null,
            'assignee_member_id' => null,
            'name' => fake()->words(3, true),
            'notes' => fake()->optional()->sentence(),
            'quantity' => 1,
            'estimated_cost' => fake()->optional()->numberBetween(50_000, 5_000_000),
            'actual_cost' => null,
            'deadline' => fake()->optional()->dateTimeBetween('-1 month', '+6 months'),
            'completed_at' => null,
            'purchase_url' => null,
        ];
    }

    public function seserahan(): static
    {
        return $this->state(fn () => [
            'type' => ChecklistType::Seserahan,
            'purchase_url' => fake()->optional()->url(),
        ]);
    }

    public function administration(): static
    {
        return $this->state(fn () => ['type' => ChecklistType::Administration]);
    }
}
