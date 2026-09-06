<?php

namespace Database\Factories;

use App\Enums\WeddingMemberRole;
use App\Models\User;
use App\Models\Wedding;
use App\Models\WeddingMember;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<WeddingMember> */
class WeddingMemberFactory extends Factory
{
    protected $model = WeddingMember::class;

    public function definition(): array
    {
        return ['wedding_id' => Wedding::factory(), 'user_id' => User::factory(), 'role' => WeddingMemberRole::Editor, 'joined_at' => now(), 'left_at' => null];
    }

    public function owner(): static
    {
        return $this->state(fn () => ['role' => WeddingMemberRole::Owner]);
    }

    public function viewer(): static
    {
        return $this->state(fn () => ['role' => WeddingMemberRole::Viewer]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['left_at' => now()]);
    }
}
