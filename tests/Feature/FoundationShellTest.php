<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class FoundationShellTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_home_renders_the_wedding_planner_welcome_page(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('welcome')
                ->where('auth.user', null),
            );
    }

    public function test_guest_cannot_access_the_authenticated_shell(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }

    public function test_verified_user_can_access_the_authenticated_shell(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('dashboard')
                ->where('auth.user.id', $user->id),
            );
    }
}
