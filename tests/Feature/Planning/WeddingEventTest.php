<?php

namespace Tests\Feature\Planning;

use App\Enums\TaskDateMode;
use App\Models\Task;
use App\Models\TaskCategory;
use App\Models\TaskStatus;
use App\Models\User;
use App\Models\Wedding;
use App\Models\WeddingEvent;
use App\Models\WeddingMember;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class WeddingEventTest extends TestCase
{
    use RefreshDatabase;

    public function test_members_can_view_events_while_only_owner_and_editor_can_mutate(): void
    {
        [$wedding, $owner] = $this->weddingWithMember('owner');
        [, $editor] = $this->addMember($wedding, 'editor');
        [, $viewer] = $this->addMember($wedding, 'viewer');

        foreach ([$owner, $editor, $viewer] as $user) {
            $this->actingAs($user)->get(route('weddings.events.index', $wedding))
                ->assertOk()
                ->assertInertia(fn (Assert $page) => $page
                    ->component('weddings/events/index')
                    ->has('events', 1));
        }

        $payload = [
            'name' => 'Resepsi',
            'type' => 'reception',
            'event_date' => '2027-06-21',
            'starts_at' => '11:00',
            'ends_at' => '14:00',
            'venue' => 'Gedung Bahagia',
            'notes' => 'Sesi keluarga lebih dulu.',
            'is_primary' => false,
        ];

        $this->actingAs($editor)->post(route('weddings.events.store', $wedding), $payload)
            ->assertRedirect(route('weddings.events.index', $wedding));
        $this->actingAs($viewer)->post(route('weddings.events.store', $wedding), $payload)
            ->assertForbidden();
    }

    public function test_members_can_view_event_detail_while_only_owner_and_editor_see_edit_access(): void
    {
        [$wedding, $owner] = $this->weddingWithMember('owner');
        [, $viewer] = $this->addMember($wedding, 'viewer');
        $event = $wedding->events()->firstOrFail();

        $this->actingAs($owner)->get(route('weddings.events.show', [$wedding, $event]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('weddings/events/show')
                ->where('event.name', 'Akad')
                ->where('canEdit', true));

        $this->actingAs($viewer)->get(route('weddings.events.show', [$wedding, $event]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('weddings/events/show')
                ->where('event.name', 'Akad')
                ->where('canEdit', false));
    }

    public function test_event_validation_rejects_an_end_time_before_start_time(): void
    {
        [$wedding, $owner] = $this->weddingWithMember('owner');

        $this->actingAs($owner)->post(route('weddings.events.store', $wedding), [
            'name' => 'Resepsi',
            'type' => 'reception',
            'event_date' => '2027-06-21',
            'starts_at' => '14:00',
            'ends_at' => '11:00',
            'is_primary' => false,
        ])->assertSessionHasErrors('ends_at');
    }

    public function test_primary_change_requires_confirmation_and_recalculates_only_relative_tasks(): void
    {
        [$wedding, $owner] = $this->weddingWithMember('owner');
        $oldPrimary = $wedding->events()->firstOrFail();
        $newPrimary = WeddingEvent::factory()->for($wedding)->create([
            'name' => 'Resepsi',
            'type' => 'reception',
            'event_date' => '2027-07-01',
        ]);
        [$category, $status] = $this->planningReferences($wedding);
        $relative = Task::factory()->for($wedding)->create([
            'task_category_id' => $category->id,
            'task_status_id' => $status->id,
            'date_mode' => TaskDateMode::TemplateRelative,
            'start_date' => '2027-06-10',
            'due_date' => '2027-06-15',
            'relative_start_offset' => -10,
            'relative_due_offset' => -5,
        ]);
        $manual = Task::factory()->for($wedding)->create([
            'task_category_id' => $category->id,
            'task_status_id' => $status->id,
            'date_mode' => TaskDateMode::Manual,
            'start_date' => '2027-05-01',
            'due_date' => '2027-05-05',
        ]);

        $this->actingAs($owner)->patch(route('weddings.events.update', [$wedding, $newPrimary]), [
            'name' => $newPrimary->name,
            'type' => $newPrimary->type,
            'event_date' => '2027-07-01',
            'is_primary' => true,
        ])->assertSessionHasErrors('confirm_recalculate_relative_tasks');

        $this->actingAs($owner)->patch(route('weddings.events.update', [$wedding, $newPrimary]), [
            'name' => $newPrimary->name,
            'type' => $newPrimary->type,
            'event_date' => '2027-07-01',
            'is_primary' => true,
            'confirm_recalculate_relative_tasks' => true,
        ])->assertRedirect(route('weddings.events.index', $wedding));

        $this->assertFalse($oldPrimary->refresh()->is_primary);
        $this->assertTrue($newPrimary->refresh()->is_primary);
        $this->assertSame('2027-06-21', $relative->refresh()->start_date->toDateString());
        $this->assertSame('2027-06-26', $relative->due_date->toDateString());
        $this->assertSame('2027-05-01', $manual->refresh()->start_date->toDateString());
    }

    public function test_primary_event_cannot_be_deleted_without_a_replacement(): void
    {
        [$wedding, $owner] = $this->weddingWithMember('owner');
        $primary = $wedding->events()->firstOrFail();

        $this->actingAs($owner)->delete(route('weddings.events.destroy', [$wedding, $primary]))
            ->assertSessionHasErrors('event');
        $this->assertDatabaseHas('wedding_events', ['id' => $primary->id]);
    }

    public function test_cross_wedding_nested_event_returns_not_found(): void
    {
        [$first, $owner] = $this->weddingWithMember('owner');
        [$second] = $this->weddingWithMember('owner');
        $foreignEvent = $second->events()->firstOrFail();

        $this->actingAs($owner)->patch(route('weddings.events.update', [$first, $foreignEvent]), [
            'name' => 'Tidak boleh',
            'type' => 'custom',
            'event_date' => '2027-06-20',
            'is_primary' => false,
        ])->assertNotFound();
    }

    /** @return array{Wedding, User} */
    private function weddingWithMember(string $role): array
    {
        $wedding = Wedding::factory()->create(['wedding_date' => '2027-06-20']);
        $user = User::factory()->create();
        WeddingMember::factory()->for($wedding)->for($user)->create(['role' => $role]);
        WeddingEvent::factory()->for($wedding)->primary()->create([
            'name' => 'Akad',
            'type' => 'akad',
            'event_date' => '2027-06-20',
        ]);

        return [$wedding, $user];
    }

    /** @return array{WeddingMember, User} */
    private function addMember(Wedding $wedding, string $role): array
    {
        $user = User::factory()->create();
        $membership = WeddingMember::factory()->for($wedding)->for($user)->create(['role' => $role]);

        return [$membership, $user];
    }

    /** @return array{TaskCategory, TaskStatus} */
    private function planningReferences(Wedding $wedding): array
    {
        return [
            TaskCategory::factory()->for($wedding)->create(),
            TaskStatus::factory()->for($wedding)->create(),
        ];
    }
}
