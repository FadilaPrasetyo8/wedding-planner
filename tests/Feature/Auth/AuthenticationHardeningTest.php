<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AuthenticationHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_model_requires_email_verification(): void
    {
        $this->assertInstanceOf(MustVerifyEmail::class, new User);
    }

    public function test_registration_is_public_and_sends_verification_notification(): void
    {
        Notification::fake();

        $response = $this->post(route('register.store'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $user = User::query()->where('email', 'test@example.com')->firstOrFail();

        $response->assertRedirect(route('dashboard', absolute: false));
        $this->assertAuthenticatedAs($user);
        $this->assertNull($user->email_verified_at);
        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_unverified_users_can_log_in_and_access_the_authenticated_dashboard(): void
    {
        $user = User::factory()->unverified()->create();

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticatedAs($user);
        $this->get(route('dashboard'))->assertOk();
    }

    public function test_registration_rejects_a_duplicate_email(): void
    {
        User::factory()->create(['email' => 'test@example.com']);

        $this->post(route('register.store'), [
            'name' => 'Other User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertDatabaseCount('users', 1);
    }

    public function test_registration_requires_a_confirmed_password(): void
    {
        $this->post(route('register.store'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'different-password',
        ])->assertSessionHasErrors('password');

        $this->assertDatabaseCount('users', 0);
    }
}
