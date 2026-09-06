<?php

namespace Tests\Feature\Workspace;

use App\Enums\WeddingMemberRole;
use App\Models\User;
use App\Models\Wedding;
use App\Models\WeddingInvitation;
use App\Models\WeddingMember;
use App\Notifications\WeddingInvitationNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class WeddingInvitationTest extends TestCase
{
    use RefreshDatabase;

    public function test_verified_owner_can_invite_any_email_as_editor_or_viewer_with_hashed_expiring_token(): void
    {
        Notification::fake();
        [$owner, $wedding] = $this->ownerAndWedding();

        $this->actingAs($owner)->post(route('weddings.invitations.store', $wedding), [
            'email' => ' NEW.Person@Example.COM ',
            'role' => WeddingMemberRole::Editor->value,
        ])->assertRedirect();

        $invitation = WeddingInvitation::query()->firstOrFail();
        $this->assertSame('new.person@example.com', $invitation->email);
        $this->assertSame(64, strlen($invitation->token_hash));
        $this->assertTrue($invitation->expires_at->between(now()->addDays(6), now()->addDays(8)));
        Notification::assertSentOnDemand(
            WeddingInvitationNotification::class,
            fn ($notification, array $channels, object $notifiable) => $notifiable->routes['mail'] === 'new.person@example.com'
                && $notification->invitation->is($invitation)
                && hash('sha256', $notification->token) === $invitation->token_hash,
        );
    }

    public function test_invitation_creation_requires_verified_owner_and_rejects_owner_role_and_duplicate_pending_email(): void
    {
        Notification::fake();
        [$owner, $wedding] = $this->ownerAndWedding(verified: false);

        $this->actingAs($owner)->post(route('weddings.invitations.store', $wedding), [
            'email' => 'person@example.com', 'role' => 'editor',
        ])->assertRedirect(route('verification.notice'));

        $owner->markEmailAsVerified();
        $this->actingAs($owner)->post(route('weddings.invitations.store', $wedding), [
            'email' => 'person@example.com', 'role' => 'owner',
        ])->assertSessionHasErrors('role');

        $this->actingAs($owner)->post(route('weddings.invitations.store', $wedding), [
            'email' => 'person@example.com', 'role' => 'viewer',
        ])->assertRedirect();
        $this->actingAs($owner)->post(route('weddings.invitations.store', $wedding), [
            'email' => 'PERSON@example.com', 'role' => 'editor',
        ])->assertSessionHasErrors('email');
    }

    public function test_matching_verified_recipient_accepts_once_and_reactivates_existing_membership(): void
    {
        Notification::fake();
        [$owner, $wedding] = $this->ownerAndWedding();
        $recipient = User::factory()->create(['email' => 'recipient@example.com']);
        WeddingMember::factory()->for($wedding)->for($recipient)->inactive()->viewer()->create();
        $token = $this->invite($owner, $wedding, $recipient->email, 'editor');

        $this->actingAs($recipient)->post(route('invitations.accept', $token))->assertRedirect(route('weddings.show', $wedding));

        $membership = WeddingMember::query()->whereBelongsTo($recipient)->whereBelongsTo($wedding)->firstOrFail();
        $this->assertNull($membership->left_at);
        $this->assertSame(WeddingMemberRole::Editor, $membership->role);
        $this->assertDatabaseCount('wedding_members', 2);

        $this->actingAs($recipient)->post(route('invitations.accept', $token))->assertGone();
    }

    public function test_accept_and_decline_require_matching_verified_email_and_valid_token(): void
    {
        Notification::fake();
        [$owner, $wedding] = $this->ownerAndWedding();
        $recipient = User::factory()->create(['email' => 'recipient@example.com']);
        $wrongUser = User::factory()->create(['email' => 'wrong@example.com']);
        $recipient->forceFill(['email_verified_at' => null])->save();
        $token = $this->invite($owner, $wedding, $recipient->email, 'viewer');

        $this->actingAs($wrongUser)->post(route('invitations.accept', $token))->assertForbidden();
        $this->actingAs($recipient)->post(route('invitations.accept', $token))->assertRedirect(route('verification.notice'));
        $recipient->markEmailAsVerified();
        $this->actingAs($recipient)->post(route('invitations.decline', $token))->assertRedirect(route('dashboard'));
        $this->actingAs($recipient)->post(route('invitations.accept', $token))->assertGone();
        $this->actingAs($recipient)->post(route('invitations.accept', 'invalid-token'))->assertNotFound();
    }

    public function test_verified_owner_can_cancel_pending_invitation_but_editor_cannot(): void
    {
        Notification::fake();
        [$owner, $wedding] = $this->ownerAndWedding();
        $editor = User::factory()->create();
        WeddingMember::factory()->for($wedding)->for($editor)->create();
        $token = $this->invite($owner, $wedding, 'person@example.com', 'viewer');
        $invitation = WeddingInvitation::query()->firstOrFail();

        $this->actingAs($editor)->delete(route('weddings.invitations.destroy', [$wedding, $invitation]))->assertForbidden();
        $this->actingAs($owner)->delete(route('weddings.invitations.destroy', [$wedding, $invitation]))->assertRedirect();
        $this->assertNotNull($invitation->fresh()->cancelled_at);
    }

    /** @return array{User, Wedding} */
    private function ownerAndWedding(bool $verified = true): array
    {
        $owner = $verified ? User::factory()->create() : User::factory()->unverified()->create();
        $wedding = Wedding::factory()->create();
        WeddingMember::factory()->for($wedding)->for($owner)->owner()->create();

        return [$owner, $wedding];
    }

    private function invite(User $owner, Wedding $wedding, string $email, string $role): string
    {
        $token = null;
        Notification::assertNothingSent();
        $this->actingAs($owner)->post(route('weddings.invitations.store', $wedding), compact('email', 'role'));
        Notification::assertSentOnDemand(
            WeddingInvitationNotification::class,
            function (WeddingInvitationNotification $notification) use (&$token): bool {
                $token = $notification->token;

                return true;
            },
        );

        return $token;
    }
}
