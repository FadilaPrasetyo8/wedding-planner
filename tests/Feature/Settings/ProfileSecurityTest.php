<?php

namespace Tests\Feature\Settings;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ProfileSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_routes_require_authentication(): void
    {
        $this->get(route('profile.edit'))->assertRedirect(route('login'));
        $this->patch(route('profile.update'), [])->assertRedirect(route('login'));
        $this->delete(route('profile.destroy'), [])->assertRedirect(route('login'));
    }

    public function test_changing_email_requires_reverification_and_sends_notification(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->actingAs($user)
            ->patch(route('profile.update'), [
                'name' => $user->name,
                'email' => 'new-email@example.com',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('profile.edit'));

        $user->refresh();

        $this->assertSame('new-email@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
        Notification::assertSentTo($user, VerifyEmail::class);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk();
    }

    public function test_unchanged_email_does_not_send_verification_notification(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->actingAs($user)
            ->patch(route('profile.update'), [
                'name' => 'Updated Name',
                'email' => $user->email,
            ])
            ->assertSessionHasNoErrors();

        $this->assertNotNull($user->refresh()->email_verified_at);
        Notification::assertNothingSent();
    }

    public function test_unverified_users_can_access_authenticated_account_settings(): void
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)->get(route('appearance.edit'))->assertOk();
        $this->actingAs($user)->get(route('security.edit'))
            ->assertRedirect(route('password.confirm'));
    }
}
