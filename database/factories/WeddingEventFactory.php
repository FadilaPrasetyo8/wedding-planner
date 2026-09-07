<?php

namespace Database\Factories;

use App\Models\Wedding;
use App\Models\WeddingEvent;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<WeddingEvent> */
class WeddingEventFactory extends Factory
{
    protected $model = WeddingEvent::class;

    public function definition(): array
    {
        return [
            'wedding_id' => Wedding::factory(),
            'name' => fake()->randomElement(['Akad', 'Resepsi', 'Lamaran']),
            'type' => 'custom',
            'event_date' => fake()->dateTimeBetween('+1 month', '+1 year'),
            'starts_at' => null,
            'ends_at' => null,
            'venue' => null,
            'notes' => null,
            'is_primary' => false,
            'sort_order' => 0,
        ];
    }

    public function primary(): static
    {
        return $this->state(fn () => ['is_primary' => true]);
    }
}
