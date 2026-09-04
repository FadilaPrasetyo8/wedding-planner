<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AuthenticationRateLimitTest extends TestCase
{
    use RefreshDatabase;

    public function test_verification_notifications_are_rate_limited(): void
    {
        Notification::fake();

        $user = User::factory()->unverified()->create();

        for ($attempt = 0; $attempt < 6; $attempt++) {
            $this->actingAs($user)
                ->post(route('verification.send'))
                ->assertRedirect();
        }

        $this->actingAs($user)
            ->post(route('verification.send'))
            ->assertTooManyRequests();
    }

    public function test_password_reset_link_requests_are_rate_limited(): void
    {
        $user = User::factory()->create();

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post(route('password.email'), [
                'email' => $user->email,
            ])->assertRedirect();
        }

        $this->postJson(route('password.email'), [
            'email' => $user->email,
        ])->assertTooManyRequests();
    }

    public function test_registration_requests_are_rate_limited(): void
    {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post(route('register.store'), [
                'name' => '',
                'email' => 'invalid-email',
                'password' => 'short',
                'password_confirmation' => 'different',
            ])->assertSessionHasErrors();
        }

        $this->postJson(route('register.store'), [
            'name' => '',
            'email' => 'invalid-email',
            'password' => 'short',
            'password_confirmation' => 'different',
        ])->assertTooManyRequests();
    }
}
