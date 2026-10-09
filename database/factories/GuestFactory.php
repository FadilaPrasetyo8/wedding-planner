<?php

namespace Database\Factories;

use App\Enums\GuestInvitationStatus;
use App\Enums\GuestRsvpStatus;
use App\Models\Guest;
use App\Models\GuestGroup;
use App\Models\GuestSide;
use App\Models\Wedding;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Guest> */
class GuestFactory extends Factory
{
    protected $model = Guest::class;

    public function definition(): array
    {
        return [
            'wedding_id' => Wedding::factory(),
            'guest_side_id' => null,
            'guest_group_id' => null,
            'name' => fake()->name(),
            'phone' => fake()->optional()->numerify('08##########'),
            'invitation_count' => 1,
            'pax_count' => fake()->numberBetween(1, 4),
            'invitation_status' => GuestInvitationStatus::NotSent,
            'rsvp_status' => fake()->optional()->randomElement(GuestRsvpStatus::cases()),
            'notes' => fake()->optional()->sentence(),
        ];
    }

    public function forSide(GuestSide $side): static
    {
        return $this->state(fn () => [
            'wedding_id' => $side->wedding_id,
            'guest_side_id' => $side->id,
        ]);
    }

    public function forGroup(GuestGroup $group): static
    {
        return $this->state(fn () => [
            'wedding_id' => $group->wedding_id,
            'guest_group_id' => $group->id,
        ]);
    }
}
