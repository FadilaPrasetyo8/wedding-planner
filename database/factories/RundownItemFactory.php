<?php

namespace Database\Factories;

use App\Enums\RundownStatus;
use App\Models\RundownItem;
use App\Models\Wedding;
use App\Models\WeddingEvent;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<RundownItem> */
class RundownItemFactory extends Factory
{
    protected $model = RundownItem::class;

    public function definition(): array
    {
        return [
            'wedding_id' => Wedding::factory(),
            'wedding_event_id' => WeddingEvent::factory(),
            'title' => fake()->randomElement([
                'Penyambutan tamu',
                'Pembukaan acara',
                'Sambutan keluarga',
                'Musik pengantar',
                'Foto bersama',
            ]),
            'notes' => null,
            'pic' => fake()->optional()->name(),
            'highlights' => null,
            'status' => RundownStatus::Scheduled,
            'start_time' => fake()->optional()->time('H:i:s'),
            'end_time' => null,
            'sort_order' => 0,
        ];
    }
}
