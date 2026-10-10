<?php

namespace Tests\Feature\Logistics;

use App\Actions\Weddings\CreateWeddingAction;
use App\Models\Guest;
use App\Models\User;
use App\Models\Wedding;
use App\Models\WeddingMember;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuestTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private User $editor;

    private User $viewer;

    private Wedding $wedding;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create();
        $this->wedding = app(CreateWeddingAction::class)->execute($this->owner, [
            'name' => 'Pernikahan Test',
            'partner_one_name' => 'Ayu',
            'partner_two_name' => 'Bima',
            'wedding_date' => now()->addYear()->toDateString(),
            'timezone' => 'Asia/Jakarta',
            'initial_event_name' => 'Resepsi',
        ]);

        $this->editor = User::factory()->create();
        WeddingMember::factory()->for($this->wedding)->for($this->editor, 'user')->create();

        $this->viewer = User::factory()->create();
        WeddingMember::factory()->viewer()->for($this->wedding)->for($this->viewer, 'user')->create();
    }

    private function guest(array $overrides = []): Guest
    {
        return $this->wedding->guests()->create(array_merge([
            'name' => 'Keluarga Wijaya',
            'guest_side_id' => null,
            'guest_group_id' => null,
            'phone' => null,
            'invitation_count' => 1,
            'pax_count' => 2,
            'invitation_status' => 'not_sent',
            'rsvp_status' => null,
            'notes' => null,
        ], $overrides));
    }

    public function test_index_requires_membership_and_aggregates_match_filters(): void
    {
        $side = $this->wedding->guestSides()->where('name', 'Mempelai 1')->first();
        $this->guest(['name' => 'Keluarga Wijaya', 'guest_side_id' => $side->id, 'pax_count' => 4, 'rsvp_status' => 'attending']);
        $this->guest(['name' => 'Teman kantor', 'guest_side_id' => $side->id, 'pax_count' => 1, 'rsvp_status' => 'attending']);
        $this->guest(['name' => 'Keluarga Bima', 'pax_count' => 3, 'rsvp_status' => 'pending']);

        // Unfiltered: all three guests.
        $response = $this->actingAs($this->viewer)
            ->get(route('weddings.guests.index', $this->wedding))
            ->assertOk();
        $page = $response->viewData('page')['props'];
        $this->assertSame(3, $page['aggregates']['guestCount']);
        $this->assertSame(3, $page['aggregates']['invitationCount']);
        $this->assertSame(8, $page['aggregates']['paxCount']);
        $this->assertSame(5, $page['aggregates']['attendingCount']);
        $this->assertFalse($page['canEdit']);

        // Side-filtered: only the two guests on Mempelai 1.
        $response = $this->actingAs($this->viewer)
            ->get(route('weddings.guests.index', ['wedding' => $this->wedding, 'side' => $side->uuid]))
            ->assertOk();
        $aggregates = $response->viewData('page')['props']['aggregates'];
        $this->assertSame(2, $aggregates['guestCount']);
        $this->assertSame(5, $aggregates['paxCount']);
        $this->assertSame(5, $aggregates['attendingCount']);

        $outsider = User::factory()->create();
        $this->actingAs($outsider)
            ->get(route('weddings.guests.index', $this->wedding))
            ->assertForbidden();
    }

    public function test_editor_can_create_guest_with_uuid_references(): void
    {
        $side = $this->wedding->guestSides()->first();
        $group = $this->wedding->guestGroups()->first();

        $this->actingAs($this->editor)
            ->post(route('weddings.guests.store', $this->wedding), [
                'name' => 'Keluarga Wijaya',
                'guest_side_uuid' => $side->uuid,
                'guest_group_uuid' => $group->uuid,
                'phone' => '081234567890',
                'invitation_count' => 1,
                'pax_count' => 4,
                'invitation_status' => 'sent',
                'rsvp_status' => 'attending',
            ])->assertRedirect()->assertSessionHas('success');

        $guest = $this->wedding->guests()->where('name', 'Keluarga Wijaya')->first();
        $this->assertNotNull($guest);
        $this->assertSame($side->id, $guest->guest_side_id);
        $this->assertSame($group->id, $guest->guest_group_id);
        $this->assertSame('sent', $guest->invitation_status->value);
        $this->assertSame('attending', $guest->rsvp_status?->value);
    }

    public function test_side_uuid_from_another_wedding_is_rejected(): void
    {
        $otherOwner = User::factory()->create();
        $otherWedding = app(CreateWeddingAction::class)->execute($otherOwner, [
            'name' => 'Wedding Lain',
            'partner_one_name' => 'Cinta',
            'partner_two_name' => 'Dewa',
            'wedding_date' => now()->addYear()->toDateString(),
            'timezone' => 'Asia/Jakarta',
            'initial_event_name' => 'Resepsi',
        ]);
        $otherSide = $otherWedding->guestSides()->first();

        $this->actingAs($this->editor)
            ->post(route('weddings.guests.store', $this->wedding), [
                'name' => 'Tamu curang',
                'guest_side_uuid' => $otherSide->uuid,
                'invitation_count' => 1,
                'pax_count' => 1,
                'invitation_status' => 'not_sent',
            ])->assertRedirect()->assertSessionHasErrors('guest_side_uuid');
    }

    public function test_viewer_cannot_mutate_guests(): void
    {
        $guest = $this->guest();

        $this->actingAs($this->viewer)
            ->post(route('weddings.guests.store', $this->wedding), [
                'name' => 'Dilarang',
                'invitation_count' => 1,
                'pax_count' => 1,
                'invitation_status' => 'not_sent',
            ])->assertForbidden();

        $this->actingAs($this->viewer)
            ->delete(route('weddings.guests.destroy', ['wedding' => $this->wedding, 'guest' => $guest]))
            ->assertForbidden();
        $this->assertNotNull($guest->fresh());
    }

    public function test_side_and_group_in_use_cannot_be_deleted(): void
    {
        $side = $this->wedding->guestSides()->where('name', 'Bersama')->first();
        $this->guest(['name' => 'Tamu bersama', 'guest_side_id' => $side->id]);

        $this->actingAs($this->editor)
            ->delete(route('weddings.guest-references.destroy', [
                'wedding' => $this->wedding, 'reference' => 'sides', 'uuid' => $side->uuid,
            ]))->assertRedirect()->assertSessionHasErrors('reference');
        $this->assertNotNull($side->fresh());

        $group = $this->wedding->guestGroups()->where('name', 'VIP')->first();
        $this->guest(['name' => 'Tamu VIP', 'guest_group_id' => $group->id]);

        $this->actingAs($this->editor)
            ->delete(route('weddings.guest-references.destroy', [
                'wedding' => $this->wedding, 'reference' => 'groups', 'uuid' => $group->uuid,
            ]))->assertRedirect()->assertSessionHasErrors('reference');
        $this->assertNotNull($group->fresh());

        // Unused references can be deleted.
        $unusedGroup = $this->wedding->guestGroups()->create(['name' => 'Grup Sisa', 'sort_order' => 90]);
        $this->actingAs($this->editor)
            ->delete(route('weddings.guest-references.destroy', [
                'wedding' => $this->wedding, 'reference' => 'groups', 'uuid' => $unusedGroup->uuid,
            ]))->assertRedirect()->assertSessionHas('success');
        $this->assertNull($unusedGroup->fresh());
    }

    public function test_editor_can_add_references(): void
    {
        $this->actingAs($this->editor)
            ->post(route('weddings.guest-references.store', [
                'wedding' => $this->wedding, 'reference' => 'sides',
            ]), ['name' => 'Sisi Khusus', 'sort_order' => 80])
            ->assertRedirect()->assertSessionHas('success');

        $this->assertDatabaseHas('guest_sides', [
            'wedding_id' => $this->wedding->id,
            'name' => 'Sisi Khusus',
        ]);

        // Duplicate names per wedding are rejected.
        $this->actingAs($this->editor)
            ->post(route('weddings.guest-references.store', [
                'wedding' => $this->wedding, 'reference' => 'sides',
            ]), ['name' => 'Sisi Khusus', 'sort_order' => 85])
            ->assertRedirect()->assertSessionHasErrors('name');
    }

    public function test_guest_is_isolated_per_wedding(): void
    {
        $otherOwner = User::factory()->create();
        $otherWedding = app(CreateWeddingAction::class)->execute($otherOwner, [
            'name' => 'Wedding Lain',
            'partner_one_name' => 'Cinta',
            'partner_two_name' => 'Dewa',
            'wedding_date' => now()->addYear()->toDateString(),
            'timezone' => 'Asia/Jakarta',
            'initial_event_name' => 'Resepsi',
        ]);
        $otherGuest = $otherWedding->guests()->create([
            'name' => 'Tamu Wedding Lain',
            'guest_side_id' => null,
            'guest_group_id' => null,
            'phone' => null,
            'invitation_count' => 1,
            'pax_count' => 1,
            'invitation_status' => 'not_sent',
            'rsvp_status' => null,
            'notes' => null,
        ]);

        $this->actingAs($this->editor)
            ->patch(route('weddings.guests.update', ['wedding' => $this->wedding, 'guest' => $otherGuest]), [
                'name' => 'Peretas',
                'invitation_count' => 1,
                'pax_count' => 1,
                'invitation_status' => 'not_sent',
            ])->assertNotFound();

        $this->actingAs($this->editor)
            ->delete(route('weddings.guests.destroy', ['wedding' => $this->wedding, 'guest' => $otherGuest]))
            ->assertNotFound();
        $this->assertNotNull($otherGuest->fresh());
    }

    public function test_index_includes_inline_custom_fields_and_attachments_without_definitions(): void
    {
        // No custom-field definitions exist for this wedding: the page must
        // still surface attachments, and the values map must stay empty.
        $guest = $this->guest(['name' => 'Tamu berlampiran']);
        $member = $this->wedding->memberships()->whereNull('left_at')->where('user_id', $this->owner->id)->firstOrFail();
        \App\Models\Attachment::factory()->create([
            'wedding_id' => $this->wedding->id,
            'module' => \App\Enums\AttachmentModule::Guest->value,
            'guest_id' => $guest->id,
            'uploaded_by_member_id' => $member->id,
        ]);

        $response = $this->actingAs($this->viewer)
            ->get(route('weddings.guests.index', $this->wedding))
            ->assertOk();
        $props = $response->viewData('page')['props'];

        $this->assertSame([], $props['customFieldDefinitions']);
        $this->assertSame([], $props['customValues']);
        $this->assertCount(1, $props['guestAttachments'][$guest->uuid]);
        $this->assertArrayHasKey('filenameOriginal', $props['guestAttachments'][$guest->uuid][0]);
    }
}
