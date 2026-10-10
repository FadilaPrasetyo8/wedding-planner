<?php

namespace Tests\Feature\Files;

use App\Enums\AttachmentModule;
use App\Models\Attachment;
use App\Models\Guest;
use App\Models\User;
use App\Models\Wedding;
use App\Models\WeddingMember;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * D-028: reconciliation is a metadata-versus-storage-listing command. It
 * must surface both directions of drift — metadata without an object and
 * orphaned objects without metadata — and clean them only with --prune.
 */
class ReconcileAttachmentsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        config(['filesystems.attachment_disk' => 'local']);
    }

    public function test_report_only_mode_surfaces_both_directions_without_deleting(): void
    {
        $wedding = $this->wedding();
        $stale = $this->attachment($wedding); // metadata without object

        $orphanPath = "weddings/{$wedding->uuid}/guest/orphan.pdf";
        Storage::disk('local')->put($orphanPath, 'orphan');

        $this->artisan('attachments:reconcile')
            ->expectsOutputToContain('Metadata tanpa objek: 1')
            ->expectsOutputToContain('Objek tanpa metadata: 1')
            ->assertSuccessful();

        $this->assertDatabaseHas('attachments', ['uuid' => $stale->uuid]);
        Storage::disk('local')->assertExists($orphanPath);
    }

    public function test_prune_mode_removes_stale_metadata_and_orphaned_objects(): void
    {
        $wedding = $this->wedding();
        $stale = $this->attachment($wedding);

        $orphanPath = "weddings/{$wedding->uuid}/guest/orphan.pdf";
        Storage::disk('local')->put($orphanPath, 'orphan');

        $this->artisan('attachments:reconcile', ['--prune' => true])
            ->expectsOutputToContain('Metadata tanpa objek: 1 (dihapus)')
            ->expectsOutputToContain('Objek tanpa metadata: 1 (dihapus)')
            ->assertSuccessful();

        $this->assertDatabaseMissing('attachments', ['uuid' => $stale->uuid]);
        Storage::disk('local')->assertMissing($orphanPath);
    }

    public function test_healthy_state_reports_zero_drift(): void
    {
        $wedding = $this->wedding();
        $guest = Guest::factory()->create(['wedding_id' => $wedding->id]);

        // Upload a real object so metadata and storage agree.
        $attachment = $this->attachment($wedding, $guest->id);
        Storage::disk('local')->put($attachment->path, 'content');

        $this->artisan('attachments:reconcile')
            ->expectsOutputToContain('Metadata tanpa objek: 0')
            ->expectsOutputToContain('Objek tanpa metadata: 0')
            ->assertSuccessful();
    }

    private function wedding(): Wedding
    {
        return Wedding::factory()->create(['wedding_date' => '2027-06-20']);
    }

    private function attachment(Wedding $wedding, ?int $guestId = null): Attachment
    {
        $owner = User::factory()->create();
        WeddingMember::factory()->create(['wedding_id' => $wedding->id, 'user_id' => $owner->id, 'role' => 'owner', 'joined_at' => now()]);

        return Attachment::factory()->create([
            'wedding_id' => $wedding->id,
            'module' => AttachmentModule::Guest->value,
            'guest_id' => $guestId ?? Guest::factory()->create(['wedding_id' => $wedding->id])->id,
            'uploaded_by_member_id' => $wedding->memberships()->whereNull('left_at')->where('user_id', $owner->id)->firstOrFail()->id,
        ]);
    }
}
