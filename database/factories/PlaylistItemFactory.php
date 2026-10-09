<?php

namespace Database\Factories;

use App\Models\PlaylistItem;
use App\Models\Wedding;
use App\Models\WeddingEvent;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<PlaylistItem> */
class PlaylistItemFactory extends Factory
{
    protected $model = PlaylistItem::class;

    public function definition(): array
    {
        return [
            'wedding_id' => Wedding::factory(),
            'wedding_event_id' => WeddingEvent::factory(),
            'title' => fake()->randomElement([
                'Membaca Doa',
                'Kau Adalah',
                'Semesta dan Selamanya',
                'Kangen',
                'Runtuh',
            ]),
            'artist' => fake()->optional()->name(),
            'link' => null,
            'requested_by' => null,
            'moment' => fake()->optional()->randomElement([
                'Panggung pengantin',
                'Akad',
                'Resepsi',
                'Gathering tamu',
            ]),
            'notes' => null,
            'sort_order' => 0,
        ];
    }
}
