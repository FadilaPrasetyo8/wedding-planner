<?php

namespace Tests\Feature\Planning;

use App\Actions\Weddings\SeedWeddingPlanningDefaultsAction;
use App\Enums\TaskBehavior;
use App\Models\Task;
use App\Models\User;
use App\Models\Wedding;
use App\Models\WeddingEvent;
use App\Models\WeddingMember;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class WeddingDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    public function test_dashboard_returns_timezone_aware_planning_aggregates_and_bounded_lists(): void
    {
        CarbonImmutable::setTestNow('2027-01-10 17:30:00 UTC');
        [$wedding, $user] = $this->workspace([
            'timezone' => 'Asia/Jakarta',
            'wedding_date' => '2027-06-20',
            'planning_start_date' => '2027-01-10',
        ]);
        $category = $wedding->taskCategories()->firstOrFail();
        $todo = $wedding->taskStatuses()->where('behavior', TaskBehavior::Todo)->firstOrFail();
        $completed = $wedding->taskStatuses()->where('behavior', TaskBehavior::Completed)->firstOrFail();
        $cancelled = $wedding->taskStatuses()->where('behavior', TaskBehavior::Cancelled)->firstOrFail();

        WeddingEvent::factory()->for($wedding)->primary()->create(['name' => 'Akad', 'event_date' => '2027-01-21']);
        WeddingEvent::factory()->for($wedding)->create(['name' => 'Resepsi', 'event_date' => '2027-01-25']);
        Task::factory()->for($wedding)->create(['title' => 'Terlambat', 'task_category_id' => $category->id, 'task_status_id' => $todo->id, 'start_date' => '2027-01-01', 'due_date' => '2027-01-10']);
        Task::factory()->for($wedding)->create(['title' => 'Mendatang', 'task_category_id' => $category->id, 'task_status_id' => $todo->id, 'start_date' => '2027-01-11', 'due_date' => '2027-01-13']);
        Task::factory()->for($wedding)->create(['title' => 'Selesai', 'task_category_id' => $category->id, 'task_status_id' => $completed->id, 'start_date' => '2027-01-01', 'due_date' => '2027-01-09', 'completed_at' => now()]);
        Task::factory()->for($wedding)->create(['title' => 'Batal', 'task_category_id' => $category->id, 'task_status_id' => $cancelled->id, 'start_date' => '2027-01-01', 'due_date' => '2027-01-08']);

        $this->actingAs($user)->get(route('weddings.show', $wedding))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('weddings/show')
                ->where('dashboard.primaryEvent.name', 'Akad')
                ->where('dashboard.primaryEvent.daysUntil', 10)
                ->where('dashboard.taskSummary.total', 3)
                ->where('dashboard.taskSummary.completed', 1)
                ->where('dashboard.taskSummary.completionPercentage', 33)
                ->where('dashboard.overdueCount', 1)
                ->where('dashboard.overdueTasks.0.title', 'Terlambat')
                ->where('dashboard.upcomingTasks.0.title', 'Mendatang')
                ->where('dashboard.upcomingEvents.0.name', 'Akad')
                ->has('dashboard.statusGroups', 4)
                ->has('dashboard.categoryGroups', 4));
    }

    public function test_dashboard_is_isolated_across_three_weddings_and_viewer_can_read_it(): void
    {
        [$first] = $this->workspace();
        [$second, $viewer] = $this->workspace([], 'viewer');
        [$third] = $this->workspace();

        foreach ([$first, $second, $third] as $index => $wedding) {
            Task::factory()->for($wedding)->create([
                'title' => 'Tugas '.$index,
                'task_category_id' => $wedding->taskCategories()->firstOrFail()->id,
                'task_status_id' => $wedding->taskStatuses()->firstOrFail()->id,
            ]);
            WeddingEvent::factory()->for($wedding)->primary()->create(['name' => 'Acara '.$index]);
        }

        $this->actingAs($viewer)->get(route('weddings.show', $second))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('dashboard.primaryEvent.name', 'Acara 1')
                ->where('dashboard.taskSummary.total', 1)
                ->has('dashboard.upcomingTasks', 1)
                ->where('dashboard.upcomingTasks.0.title', 'Tugas 1')
                ->where('membershipRole', 'viewer'));
    }

    /** @param array<string, mixed> $attributes
     * @return array{Wedding, User}
     */
    private function workspace(array $attributes = [], string $role = 'owner'): array
    {
        $wedding = Wedding::factory()->create($attributes);
        app(SeedWeddingPlanningDefaultsAction::class)->execute($wedding);
        $user = User::factory()->create();
        WeddingMember::factory()->for($wedding)->for($user)->create(['role' => $role]);

        return [$wedding, $user];
    }
}
