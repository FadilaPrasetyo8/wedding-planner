<?php

namespace Tests\Feature\Planning;

use App\Actions\Weddings\SeedWeddingPlanningDefaultsAction;
use App\Enums\TaskBehavior;
use App\Models\Task;
use App\Models\User;
use App\Models\Wedding;
use App\Models\WeddingEvent;
use App\Models\WeddingMember;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PlanningListTest extends TestCase
{
    use RefreshDatabase;

    public function test_planning_list_searches_filters_sorts_and_retains_query_state(): void
    {
        [$wedding, $user] = $this->workspace();
        $category = $wedding->taskCategories()->firstOrFail();
        $todo = $wedding->taskStatuses()->where('behavior', TaskBehavior::Todo)->firstOrFail();
        $completed = $wedding->taskStatuses()->where('behavior', TaskBehavior::Completed)->firstOrFail();
        Task::factory()->for($wedding)->create(['title' => 'Pesan Gedung Utama', 'task_category_id' => $category->id, 'task_status_id' => $todo->id, 'priority' => 'urgent', 'start_date' => now()->subDays(10)->toDateString(), 'due_date' => now()->subDay()->toDateString()]);
        Task::factory()->for($wedding)->create(['title' => 'Tugas selesai', 'task_category_id' => $category->id, 'task_status_id' => $completed->id, 'priority' => 'low', 'start_date' => now()->subDays(10)->toDateString(), 'due_date' => now()->subDays(3)->toDateString(), 'completed_at' => now()]);

        $this->actingAs($user)->get(route('weddings.planning.index', [$wedding, 'search' => 'Gedung', 'priority' => 'urgent', 'overdue' => 1, 'sort' => 'title', 'direction' => 'asc']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('weddings/planning/index')
                ->has('tasks.data', 1)
                ->where('tasks.data.0.title', 'Pesan Gedung Utama')
                ->where('filters.search', 'Gedung')
                ->where('filters.overdue', true));
    }

    public function test_planning_list_supports_relationship_and_date_range_filters(): void
    {
        [$wedding, $user] = $this->workspace();
        $category = $wedding->taskCategories()->firstOrFail();
        $otherCategory = $wedding->taskCategories()->whereKeyNot($category->id)->firstOrFail();
        $status = $wedding->taskStatuses()->where('behavior', TaskBehavior::InProgress)->firstOrFail();
        $otherStatus = $wedding->taskStatuses()->where('behavior', TaskBehavior::Todo)->firstOrFail();
        $category->update(['is_active' => false]);
        $status->update(['is_active' => false]);
        $event = WeddingEvent::factory()->for($wedding)->primary()->create();
        $otherEvent = WeddingEvent::factory()->for($wedding)->create();
        $assignee = WeddingMember::factory()->for($wedding)->create();
        $otherAssignee = WeddingMember::factory()->for($wedding)->create();

        Task::factory()->for($wedding)->create([
            'title' => 'Tugas cocok',
            'task_category_id' => $category->id,
            'task_status_id' => $status->id,
            'assignee_membership_id' => $assignee->id,
            'wedding_event_id' => $event->id,
            'start_date' => '2027-02-10',
            'due_date' => '2027-02-20',
        ]);
        Task::factory()->for($wedding)->create([
            'title' => 'Tugas lain',
            'task_category_id' => $otherCategory->id,
            'task_status_id' => $otherStatus->id,
            'assignee_membership_id' => $otherAssignee->id,
            'wedding_event_id' => $otherEvent->id,
            'start_date' => '2027-04-01',
            'due_date' => '2027-04-10',
        ]);

        $this->actingAs($user)->get(route('weddings.planning.index', [
            $wedding,
            'status' => $status->uuid,
            'category' => $category->uuid,
            'assignee' => $assignee->id,
            'event' => $event->uuid,
            'date_from' => '2027-02-15',
            'date_to' => '2027-02-28',
        ]))->assertOk()->assertInertia(fn (Assert $page) => $page
            ->has('tasks.data', 1)
            ->where('tasks.data.0.title', 'Tugas cocok')
            ->where('filters.status', $status->uuid)
            ->where('filters.category', $category->uuid)
            ->where('filters.assignee', $assignee->id)
            ->where('filters.event', $event->uuid)
            ->where('filters.date_from', '2027-02-15')
            ->where('filters.date_to', '2027-02-28')
            ->where('categories', fn ($categories): bool => collect($categories)->contains('uuid', $category->uuid))
            ->where('statuses', fn ($statuses): bool => collect($statuses)->contains('uuid', $status->uuid)));
    }

    public function test_planning_list_rejects_invalid_filters_and_foreign_relationships(): void
    {
        [$wedding, $user] = $this->workspace();
        [$other] = $this->workspace();

        $this->actingAs($user)->from(route('weddings.planning.index', $wedding))->get(route('weddings.planning.index', [
            $wedding,
            'status' => $other->taskStatuses()->firstOrFail()->uuid,
            'sort' => 'wedding_id',
            'direction' => 'sideways',
        ]))->assertRedirect(route('weddings.planning.index', $wedding))
            ->assertSessionHasErrors(['status', 'sort', 'direction']);
    }

    public function test_planning_list_applies_each_allowlisted_sort_deterministically(): void
    {
        [$wedding, $user] = $this->workspace();
        $category = $wedding->taskCategories()->firstOrFail();
        $status = $wedding->taskStatuses()->firstOrFail();

        Task::factory()->for($wedding)->create([
            'title' => 'Zulu',
            'task_category_id' => $category->id,
            'task_status_id' => $status->id,
            'priority' => 'urgent',
            'start_date' => '2027-03-01',
            'due_date' => '2027-03-10',
        ]);
        Task::factory()->for($wedding)->create([
            'title' => 'Alpha',
            'task_category_id' => $category->id,
            'task_status_id' => $status->id,
            'priority' => 'low',
            'start_date' => '2027-02-01',
            'due_date' => '2027-02-10',
        ]);

        foreach (['title', 'start_date', 'due_date', 'priority', 'status', 'category', 'updated_at'] as $sort) {
            $this->actingAs($user)->get(route('weddings.planning.index', [$wedding, 'sort' => $sort, 'direction' => 'asc']))
                ->assertOk()
                ->assertInertia(fn (Assert $page) => $page->has('tasks.data', 2));
        }
    }

    public function test_planning_pagination_retains_filter_query_parameters(): void
    {
        [$wedding, $user] = $this->workspace();
        $category = $wedding->taskCategories()->firstOrFail();
        $status = $wedding->taskStatuses()->firstOrFail();

        Task::factory()->count(21)->for($wedding)->create([
            'task_category_id' => $category->id,
            'task_status_id' => $status->id,
            'priority' => 'high',
        ]);

        $this->actingAs($user)->get(route('weddings.planning.index', [$wedding, 'priority' => 'high', 'sort' => 'title']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('tasks.data', 20)
                ->where('tasks.links.2.url', fn (?string $url): bool => $url !== null
                    && str_contains($url, 'priority=high')
                    && str_contains($url, 'sort=title')));
    }

    public function test_planning_list_is_isolated_across_three_weddings(): void
    {
        [$first, $user] = $this->workspace();
        [$second] = $this->workspace();
        [$third] = $this->workspace();
        foreach ([$first, $second, $third] as $index => $wedding) {
            Task::factory()->for($wedding)->create([
                'title' => 'Tugas '.$index,
                'task_category_id' => $wedding->taskCategories()->firstOrFail()->id,
                'task_status_id' => $wedding->taskStatuses()->firstOrFail()->id,
            ]);
        }

        $this->actingAs($user)->get(route('weddings.planning.index', $first))
            ->assertInertia(fn (Assert $page) => $page
                ->has('tasks.data', 1)
                ->where('tasks.data.0.title', 'Tugas 0'));
    }

    /** @return array{Wedding, User} */
    private function workspace(): array
    {
        $wedding = Wedding::factory()->create();
        app(SeedWeddingPlanningDefaultsAction::class)->execute($wedding);
        $user = User::factory()->create();
        WeddingMember::factory()->for($wedding)->for($user)->owner()->create();

        return [$wedding, $user];
    }
}
