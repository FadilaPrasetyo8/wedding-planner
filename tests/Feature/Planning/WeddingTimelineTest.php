<?php

namespace Tests\Feature\Planning;

use App\Actions\Weddings\SeedWeddingPlanningDefaultsAction;
use App\Models\Task;
use App\Models\User;
use App\Models\Wedding;
use App\Models\WeddingEvent;
use App\Models\WeddingMember;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class WeddingTimelineTest extends TestCase
{
    use RefreshDatabase;

    public function test_timeline_reuses_list_filters_and_keeps_markers_wedding_scoped(): void
    {
        $wedding = Wedding::factory()->create();
        app(SeedWeddingPlanningDefaultsAction::class)->execute($wedding);
        $user = User::factory()->create();
        WeddingMember::factory()->for($wedding)->for($user)->viewer()->create();
        $category = $wedding->taskCategories()->firstOrFail();
        $status = $wedding->taskStatuses()->firstOrFail();
        $category->update(['is_active' => false]);
        $status->update(['is_active' => false]);
        Task::factory()->for($wedding)->create(['title' => 'Dekorasi timeline', 'priority' => 'high', 'task_category_id' => $category->id, 'task_status_id' => $status->id, 'start_date' => '2027-01-01', 'due_date' => '2027-01-10']);
        WeddingEvent::factory()->for($wedding)->primary()->create(['name' => 'Akad', 'event_date' => '2027-06-20']);
        $other = Wedding::factory()->create();
        WeddingEvent::factory()->for($other)->primary()->create(['name' => 'Acara Rahasia', 'event_date' => '2027-02-01']);

        $this->actingAs($user)->get(route('weddings.planning.timeline', [$wedding, 'search' => 'Dekorasi', 'priority' => 'high']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('weddings/planning/timeline')
                ->has('tasks', 1)
                ->where('tasks.0.title', 'Dekorasi timeline')
                ->has('events', 1)
                ->where('events.0.name', 'Akad')
                ->where('categories', fn ($categories): bool => collect($categories)->contains('uuid', $category->uuid))
                ->where('statuses', fn ($statuses): bool => collect($statuses)->contains('uuid', $status->uuid))
                ->where('canEdit', false));
    }

    public function test_timeline_applies_date_range_and_overdue_semantics(): void
    {
        $wedding = Wedding::factory()->create(['timezone' => 'Asia/Jakarta']);
        app(SeedWeddingPlanningDefaultsAction::class)->execute($wedding);
        $user = User::factory()->create();
        WeddingMember::factory()->for($wedding)->for($user)->owner()->create();
        $category = $wedding->taskCategories()->firstOrFail();
        $status = $wedding->taskStatuses()->firstOrFail();

        Task::factory()->for($wedding)->create([
            'title' => 'Terlambat dalam rentang',
            'task_category_id' => $category->id,
            'task_status_id' => $status->id,
            'start_date' => now('Asia/Jakarta')->subDays(5)->toDateString(),
            'due_date' => now('Asia/Jakarta')->subDay()->toDateString(),
        ]);
        Task::factory()->for($wedding)->create([
            'title' => 'Di luar rentang',
            'task_category_id' => $category->id,
            'task_status_id' => $status->id,
            'start_date' => now('Asia/Jakarta')->addMonths(2)->toDateString(),
            'due_date' => now('Asia/Jakarta')->addMonths(2)->addDay()->toDateString(),
        ]);

        $this->actingAs($user)->get(route('weddings.planning.timeline', [
            $wedding,
            'overdue' => 1,
            'date_from' => now('Asia/Jakarta')->subDays(3)->toDateString(),
            'date_to' => now('Asia/Jakarta')->toDateString(),
        ]))->assertOk()->assertInertia(fn (Assert $page) => $page
            ->has('tasks', 1)
            ->where('tasks.0.title', 'Terlambat dalam rentang')
            ->where('tasks.0.isOverdue', true)
            ->where('filters.overdue', true));
    }

    public function test_timeline_payload_is_bounded_to_two_hundred_tasks_and_fifty_events(): void
    {
        $wedding = Wedding::factory()->create();
        app(SeedWeddingPlanningDefaultsAction::class)->execute($wedding);
        $user = User::factory()->create();
        WeddingMember::factory()->for($wedding)->for($user)->owner()->create();
        $category = $wedding->taskCategories()->firstOrFail();
        $status = $wedding->taskStatuses()->firstOrFail();

        Task::factory()->count(205)->for($wedding)->create([
            'task_category_id' => $category->id,
            'task_status_id' => $status->id,
        ]);
        foreach (range(1, 55) as $index) {
            WeddingEvent::factory()->for($wedding)->create([
                'name' => 'Acara '.$index,
                'event_date' => now()->addDays($index)->toDateString(),
                'sort_order' => $index,
            ]);
        }

        $this->actingAs($user)->get(route('weddings.planning.timeline', $wedding))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('tasks', 200)
                ->has('events', 50));
    }
}
