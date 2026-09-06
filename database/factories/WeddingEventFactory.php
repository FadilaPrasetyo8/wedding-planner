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
        return ['wedding_id' => Wedding::factory(), 'name' => fake()->randomElement(['Akad', 'Resepsi', 'Lamaran']), 'event_date' => fake()->dateTimeBetween('+1 month', '+1 year'), 'is_primary' => false, 'sort_order' => 0];
    }
}
