<?php

namespace Tests\Feature\Files;

use App\Enums\AttachmentModule;
use App\Models\Attachment;
use App\Models\Guest;
use App\Models\User;
use App\Models\Wedding;
use App\Models\WeddingMember;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AttachmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        config(['filesystems.attachment_disk' => 'local']);
    }

    public function test_verified_editor_can_upload_and_member_can_download(): void
    {
        [$wedding, $owner] = $this->workspace();
        $owner->markEmailAsVerified();
        $guest = $this->guest($wedding);
        $viewer = $this->member($wedding, 'viewer');

        $this->actingAs($owner)->post(route('weddings.attachments.store', $wedding), [
            'module' => 'guest',
            'subject_uuid' => $guest->uuid,
            'file' => UploadedFile::fake()->create('identitas.pdf', 100, 'application/pdf'),
        ])->assertRedirect();

        $attachment = $wedding->attachments()->firstOrFail();

        // Object stored at a UUID path under the wedding namespace, never the original filename.
        $this->assertStringStartsWith("weddings/{$wedding->uuid}/guest/", $attachment->path);
        $this->assertStringEndsWith('.pdf', $attachment->path);
        $this->assertNotEquals('identitas.pdf', basename($attachment->path));
        Storage::disk('local')->assertExists($attachment->path);

        // Every active member can obtain the short-lived signed URL.
        $this->actingAs($viewer)->get(route('weddings.attachments.download', [$wedding, $attachment]))
            ->assertRedirect(Storage::disk('local')->temporaryUrl($attachment->path, now()->addMinutes(10)));
    }

    public function test_unverified_user_cannot_upload_when_verification_is_required(): void
    {
        config(['app.require_email_verification' => true]);
        [$wedding, $owner] = $this->workspace();
        $owner->forceFill(['email_verified_at' => null])->save();
        $guest = $this->guest($wedding);

        $this->actingAs($owner)->post(route('weddings.attachments.store', $wedding), [
            'module' => 'guest',
            'subject_uuid' => $guest->uuid,
            'file' => UploadedFile::fake()->create('identitas.pdf', 100, 'application/pdf'),
        ])->assertRedirectToRoute('verification.notice');
    }

    public function test_local_verification_bypass_still_runs_attachment_authorization(): void
    {
        config(['app.require_email_verification' => false]);
        [$wedding, $owner] = $this->workspace();
        $owner->forceFill(['email_verified_at' => null])->save();
        $guest = $this->guest($wedding);

        $this->actingAs($owner)->post(route('weddings.attachments.store', $wedding), [
            'module' => 'guest',
            'subject_uuid' => $guest->uuid,
            'file' => UploadedFile::fake()->create('identitas.pdf', 100, 'application/pdf'),
        ])->assertRedirect();

        $this->assertDatabaseHas('attachments', [
            'wedding_id' => $wedding->id,
            'guest_id' => $guest->id,
        ]);

        $viewer = $this->member($wedding, 'viewer');
        $viewer->forceFill(['email_verified_at' => null])->save();

        $this->actingAs($viewer)->post(route('weddings.attachments.store', $wedding), [
            'module' => 'guest',
            'subject_uuid' => $guest->uuid,
            'file' => UploadedFile::fake()->create('viewer.pdf', 100, 'application/pdf'),
        ])->assertForbidden();
    }

    public function test_email_verification_is_enabled_by_default(): void
    {
        $this->assertTrue((bool) config('app.require_email_verification'));
    }

    public function test_upload_rejects_bad_extension_mime_and_size(): void
    {
        [$wedding, $owner] = $this->workspace();
        $owner->markEmailAsVerified();
        $guest = $this->guest($wedding);

        $this->actingAs($owner)->post(route('weddings.attachments.store', $wedding), [
            'module' => 'guest',
            'subject_uuid' => $guest->uuid,
            'file' => UploadedFile::fake()->create('skrip.txt', 10, 'text/plain'),
        ])->assertStatus(422);

        // Correct extension, mismatched detected MIME type.
        $this->actingAs($owner)->post(route('weddings.attachments.store', $wedding), [
            'module' => 'guest',
            'subject_uuid' => $guest->uuid,
            'file' => UploadedFile::fake()->create('gambar.pdf', 10, 'text/plain'),
        ])->assertStatus(422);

        $this->actingAs($owner)->post(route('weddings.attachments.store', $wedding), [
            'module' => 'guest',
            'subject_uuid' => $guest->uuid,
            'file' => UploadedFile::fake()->create('besar.pdf', 11000, 'application/pdf'),
        ])->assertSessionHasErrors(['file'])->assertStatus(302);

        $this->assertSame(0, $wedding->attachments()->count());
        $this->assertSame(0, Storage::disk('local')->allFiles() === [] ? 0 : count(Storage::disk('local')->allFiles()));
    }

    public function test_upload_rejects_cross_wedding_subject(): void
    {
        [$wedding, $owner] = $this->workspace();
        [$other] = $this->workspace();
        $owner->markEmailAsVerified();
        $foreignGuest = $this->guest($other);

        $this->actingAs($owner)->post(route('weddings.attachments.store', $wedding), [
            'module' => 'guest',
            'subject_uuid' => $foreignGuest->uuid,
            'file' => UploadedFile::fake()->create('identitas.pdf', 100, 'application/pdf'),
        ])->assertNotFound();

        $this->assertSame(0, $wedding->attachments()->count());
    }

    public function test_unauthorized_users_cannot_obtain_signed_urls(): void
    {
        [$wedding, $owner] = $this->workspace();
        $owner->markEmailAsVerified();
        $guest = $this->guest($wedding);
        $stranger = User::factory()->create();
        [$other, $otherOwner] = $this->workspace();
        $otherOwner->markEmailAsVerified();

        $this->actingAs($owner)->post(route('weddings.attachments.store', $wedding), [
            'module' => 'guest',
            'subject_uuid' => $guest->uuid,
            'file' => UploadedFile::fake()->create('identitas.pdf', 100, 'application/pdf'),
        ])->assertRedirect();

        $attachment = $wedding->attachments()->firstOrFail();

        // Cross-wedding member: the wedding.member middleware rejects with 403
        // before scoped binding is even reached, never a signed URL.
        $this->actingAs($otherOwner)->get(route('weddings.attachments.download', [$wedding, $attachment]))->assertForbidden();

        // Non-member: 403 from the wedding.member middleware, never a signed URL.
        $this->actingAs($stranger)->get(route('weddings.attachments.download', [$wedding, $attachment]))->assertForbidden();

        // Not authenticated: redirected to login. actingAs() persists for the
        // rest of the test method, so this must be asserted in a fresh test.
        $this->post('logout');

        $this->get(route('weddings.attachments.download', [$wedding, $attachment]))->assertRedirect(route('login'));
    }

    public function test_delete_removes_object_and_metadata(): void
    {
        [$wedding, $owner] = $this->workspace();
        $owner->markEmailAsVerified();
        $guest = $this->guest($wedding);

        $this->actingAs($owner)->post(route('weddings.attachments.store', $wedding), [
            'module' => 'guest',
            'subject_uuid' => $guest->uuid,
            'file' => UploadedFile::fake()->create('identitas.pdf', 100, 'application/pdf'),
        ])->assertRedirect();

        $attachment = $wedding->attachments()->firstOrFail();
        $path = $attachment->path;

        $this->actingAs($owner)->delete(route('weddings.attachments.destroy', [$wedding, $attachment]))->assertRedirect();

        $this->assertSame(0, $wedding->attachments()->count());
        Storage::disk('local')->assertMissing($path);
    }

    public function test_viewer_cannot_upload_or_delete(): void
    {
        [$wedding, $owner] = $this->workspace();
        $owner->markEmailAsVerified();
        $viewer = $this->member($wedding, 'viewer');
        $viewer->markEmailAsVerified();
        $guest = $this->guest($wedding);

        $this->actingAs($viewer)->post(route('weddings.attachments.store', $wedding), [
            'module' => 'guest',
            'subject_uuid' => $guest->uuid,
            'file' => UploadedFile::fake()->create('identitas.pdf', 100, 'application/pdf'),
        ])->assertForbidden();

        $attachment = Attachment::factory()->create([
            'wedding_id' => $wedding->id,
            'module' => AttachmentModule::Guest->value,
            'guest_id' => $guest->id,
            'uploaded_by_member_id' => $wedding->memberships()->whereNull('left_at')->where('user_id', $owner->id)->firstOrFail()->id,
        ]);

        $this->actingAs($viewer)->delete(route('weddings.attachments.destroy', [$wedding, $attachment]))->assertForbidden();
    }

    public function test_missing_object_returns_gone_not_fake_url(): void
    {
        [$wedding, $owner] = $this->workspace();
        $owner->markEmailAsVerified();

        $attachment = Attachment::factory()->create([
            'wedding_id' => $wedding->id,
            'module' => AttachmentModule::Guest->value,
            'guest_id' => $this->guest($wedding)->id,
            'uploaded_by_member_id' => $wedding->memberships()->whereNull('left_at')->where('user_id', $owner->id)->firstOrFail()->id,
        ]);

        // Metadata exists but the object never did: no URL, explicit 410.
        $this->actingAs($owner)->get(route('weddings.attachments.download', [$wedding, $attachment]))->assertStatus(410);
    }

    public function test_failed_metadata_write_leaves_no_object(): void
    {
        [$wedding, $owner] = $this->workspace();
        $owner->markEmailAsVerified();
        $guest = $this->guest($wedding);

        // Force the metadata insert to fail after the object was stored.
        $this->partialMock(\App\Support\Files\StoreAttachmentAction::class)
            ->shouldReceive('execute')
            ->andReturnUsing(function (): never {
                Storage::disk('local')->put('weddings/compensation-marker/guest/x.pdf', 'probe');
                abort(500, 'Berkas gagal disimpan.');
            });

        $this->actingAs($owner)->post(route('weddings.attachments.store', $wedding), [
            'module' => 'guest',
            'subject_uuid' => $guest->uuid,
            'file' => UploadedFile::fake()->create('identitas.pdf', 100, 'application/pdf'),
        ])->assertStatus(500);

        $this->assertSame(0, $wedding->attachments()->count());
    }

    /** @return array{Wedding, User} */
    private function workspace(): array
    {
        $wedding = Wedding::factory()->create(['wedding_date' => '2027-06-20']);
        $owner = User::factory()->create();
        WeddingMember::factory()->create(['wedding_id' => $wedding->id, 'user_id' => $owner->id, 'role' => 'owner', 'joined_at' => now()]);

        return [$wedding, $owner];
    }

    private function member(Wedding $wedding, string $role): User
    {
        $user = User::factory()->create();
        WeddingMember::factory()->create(['wedding_id' => $wedding->id, 'user_id' => $user->id, 'role' => $role, 'joined_at' => now()]);

        return $user;
    }

    private function guest(Wedding $wedding): Guest
    {
        return Guest::factory()->create(['wedding_id' => $wedding->id]);
    }
}
