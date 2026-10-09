<?php

namespace Tests\Feature\EventDay;

use App\Actions\Weddings\SeedWeddingPlanningDefaultsAction;
use App\Models\PlaylistItem;
use App\Models\RundownItem;
use App\Models\User;
use App\Models\Wedding;
use App\Models\WeddingEvent;
use App\Models\WeddingMember;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RundownPlaylistTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_and_editor_can_manage_rundown_while_viewer_cannot(): void
    {
        [$wedding, $owner] = $this->workspace();
        $editor = $this->member($wedding, 'editor');
        $viewer = $this->member($wedding, 'viewer');
        $event = $wedding->events()->firstOrFail();

        $payload = [
            'event_uuid' => $event->uuid,
            'title' => 'Penyambutan tamu',
            'pic' => 'Operator musik',
            'status' => 'scheduled',
            'start_time' => '08:00',
            'end_time' => '09:00',
        ];

        $this->actingAs($owner)->post(route('weddings.rundown.store', $wedding), $payload)->assertRedirect();
        $this->actingAs($editor)->post(route('weddings.rundown.store', $wedding), [...$payload, 'title' => 'Akad nikah'])->assertRedirect();
        $this->actingAs($viewer)->post(route('weddings.rundown.store', $wedding), [...$payload, 'title' => 'Tidak boleh'])->assertForbidden();
        $this->assertSame(2, $wedding->rundownItems()->count());
    }

    public function test_rundown_requires_event_from_same_wedding_and_valid_times(): void
    {
        [$wedding, $owner] = $this->workspace();
        [$other] = $this->workspace();
        $foreignEvent = $other->events()->firstOrFail();

        $this->actingAs($owner)->post(route('weddings.rundown.store', $wedding), [
            'event_uuid' => $foreignEvent->uuid,
            'title' => 'Acara asing',
            'status' => 'scheduled',
            'start_time' => '10:00',
            'end_time' => '09:00',
        ])->assertSessionHasErrors(['event_uuid', 'end_time']);
    }

    public function test_rundown_reorder_persists_new_order_within_one_event(): void
    {
        [$wedding, $owner] = $this->workspace();
        $event = $wedding->events()->firstOrFail();
        $first = RundownItem::factory()->create(['wedding_id' => $wedding->id, 'wedding_event_id' => $event->id, 'title' => 'Pertama', 'sort_order' => 10]);
        $second = RundownItem::factory()->create(['wedding_id' => $wedding->id, 'wedding_event_id' => $event->id, 'title' => 'Kedua', 'sort_order' => 20]);

        $this->actingAs($owner)->post(route('weddings.rundown.reorder', $wedding), [
            'items' => [
                ['uuid' => $second->uuid],
                ['uuid' => $first->uuid],
            ],
        ])->assertRedirect();

        $this->assertTrue($second->refresh()->sort_order < $first->refresh()->sort_order);
    }

    public function test_rundown_reorder_rejects_items_from_different_events(): void
    {
        [$wedding, $owner] = $this->workspace();
        $primary = $wedding->events()->firstOrFail();
        $otherEvent = WeddingEvent::factory()->create(['wedding_id' => $wedding->id, 'event_date' => '2027-06-19']);
        $itemA = RundownItem::factory()->create(['wedding_id' => $wedding->id, 'wedding_event_id' => $primary->id]);
        $itemB = RundownItem::factory()->create(['wedding_id' => $wedding->id, 'wedding_event_id' => $otherEvent->id]);

        $this->actingAs($owner)->post(route('weddings.rundown.reorder', $wedding), [
            'items' => [
                ['uuid' => $itemA->uuid],
                ['uuid' => $itemB->uuid],
            ],
        ])->assertStatus(422);
    }

    public function test_viewer_sees_read_only_rundown_without_edit_forms(): void
    {
        [$wedding] = $this->workspace();
        $viewer = $this->member($wedding, 'viewer');

        $this->actingAs($viewer)->get(route('weddings.rundown.index', $wedding))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('weddings/event-day/rundown')
                ->where('canEdit', false));
    }

    public function test_cross_wedding_rundown_item_returns_not_found(): void
    {
        [$wedding, $owner] = $this->workspace();
        [$other] = $this->workspace();
        $foreignEvent = $other->events()->firstOrFail();
        $item = RundownItem::factory()->create(['wedding_id' => $other->id, 'wedding_event_id' => $foreignEvent->id]);

        $this->actingAs($owner)->patch(route('weddings.rundown.update', [$wedding, $item]), [
            'event_uuid' => $foreignEvent->uuid,
            'title' => 'Perubahan asing',
            'status' => 'completed',
        ])->assertNotFound();
        $this->actingAs($owner)->delete(route('weddings.rundown.destroy', [$wedding, $item]))->assertNotFound();
    }

    public function test_owner_and_editor_can_manage_playlist_while_viewer_cannot(): void
    {
        [$wedding, $owner] = $this->workspace();
        $editor = $this->member($wedding, 'editor');
        $viewer = $this->member($wedding, 'viewer');
        $event = $wedding->events()->firstOrFail();

        $payload = [
            'event_uuid' => $event->uuid,
            'title' => 'Bridal Chorus',
            'artist' => 'Wagner',
            'requested_by' => 'Mempelai',
            'moment' => 'Masuk pelaminan',
        ];

        $this->actingAs($owner)->post(route('weddings.playlist.store', $wedding), $payload)->assertRedirect();
        $this->actingAs($editor)->post(route('weddings.playlist.store', $wedding), [...$payload, 'title' => 'Diris'])->assertRedirect();
        $this->actingAs($viewer)->post(route('weddings.playlist.store', $wedding), [...$payload, 'title' => 'Tidak boleh'])->assertForbidden();
        $this->assertSame(2, $wedding->playlistItems()->count());
    }

    public function test_playlist_reorder_persists_new_order_within_one_event(): void
    {
        [$wedding, $owner] = $this->workspace();
        $event = $wedding->events()->firstOrFail();
        $first = PlaylistItem::factory()->create(['wedding_id' => $wedding->id, 'wedding_event_id' => $event->id, 'title' => 'Pertama', 'sort_order' => 10]);
        $second = PlaylistItem::factory()->create(['wedding_id' => $wedding->id, 'wedding_event_id' => $event->id, 'title' => 'Kedua', 'sort_order' => 20]);

        $this->actingAs($owner)->post(route('weddings.playlist.reorder', $wedding), [
            'items' => [
                ['uuid' => $second->uuid],
                ['uuid' => $first->uuid],
            ],
        ])->assertRedirect();

        $this->assertTrue($second->refresh()->sort_order < $first->refresh()->sort_order);
    }

    public function test_playlist_reorder_rejects_items_from_different_events(): void
    {
        [$wedding, $owner] = $this->workspace();
        $primary = $wedding->events()->firstOrFail();
        $otherEvent = WeddingEvent::factory()->create(['wedding_id' => $wedding->id, 'event_date' => '2027-06-19']);
        $itemA = PlaylistItem::factory()->create(['wedding_id' => $wedding->id, 'wedding_event_id' => $primary->id]);
        $itemB = PlaylistItem::factory()->create(['wedding_id' => $wedding->id, 'wedding_event_id' => $otherEvent->id]);

        $this->actingAs($owner)->post(route('weddings.playlist.reorder', $wedding), [
            'items' => [
                ['uuid' => $itemA->uuid],
                ['uuid' => $itemB->uuid],
            ],
        ])->assertStatus(422);
    }

    public function test_viewer_sees_read_only_playlist_without_edit_forms(): void
    {
        [$wedding] = $this->workspace();
        $viewer = $this->member($wedding, 'viewer');

        $this->actingAs($viewer)->get(route('weddings.playlist.index', $wedding))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('weddings/event-day/playlist')
                ->where('canEdit', false));
    }

    public function test_cross_wedding_playlist_item_returns_not_found(): void
    {
        [$wedding, $owner] = $this->workspace();
        [$other] = $this->workspace();
        $foreignEvent = $other->events()->firstOrFail();
        $item = PlaylistItem::factory()->create(['wedding_id' => $other->id, 'wedding_event_id' => $foreignEvent->id]);

        $this->actingAs($owner)->patch(route('weddings.playlist.update', [$wedding, $item]), [
            'event_uuid' => $foreignEvent->uuid,
            'title' => 'Perubahan asing',
        ])->assertNotFound();
        $this->actingAs($owner)->delete(route('weddings.playlist.destroy', [$wedding, $item]))->assertNotFound();
    }

    public function test_print_view_renders_stored_rundown_order(): void
    {
        [$wedding, $owner] = $this->workspace();
        $event = $wedding->events()->firstOrFail();
        RundownItem::factory()->create(['wedding_id' => $wedding->id, 'wedding_event_id' => $event->id, 'title' => 'Pertama', 'sort_order' => 10, 'start_time' => '08:00', 'end_time' => '09:00']);
        RundownItem::factory()->create(['wedding_id' => $wedding->id, 'wedding_event_id' => $event->id, 'title' => 'Kedua', 'sort_order' => 20]);

        $this->actingAs($owner)->get(route('weddings.rundown.print', ['wedding' => $wedding, 'event' => $event->uuid]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('weddings/event-day/rundown-print')
                ->where('items.0.title', 'Pertama')
                ->where('items.1.title', 'Kedua')
                ->where('items.0.startTime', '08:00'));
    }

    public function test_print_view_is_not_editable_and_requires_membership(): void
    {
        [$wedding] = $this->workspace();
        $viewer = $this->member($wedding, 'viewer');
        $stranger = User::factory()->create();

        $this->actingAs($viewer)->get(route('weddings.rundown.print', $wedding))->assertOk();
        $this->actingAs($stranger)->get(route('weddings.rundown.print', $wedding))->assertForbidden();
    }

    /** @return array{Wedding, User} */
    private function workspace(): array
    {
        $wedding = Wedding::factory()->create(['wedding_date' => '2027-06-20']);
        app(SeedWeddingPlanningDefaultsAction::class)->execute($wedding);
        WeddingEvent::factory()->create(['wedding_id' => $wedding->id, 'event_date' => '2027-06-20', 'is_primary' => true]);
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
}
