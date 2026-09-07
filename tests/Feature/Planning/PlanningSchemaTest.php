<?php

namespace Tests\Feature\Planning;

use App\Actions\Weddings\SeedWeddingPlanningDefaultsAction;
use App\Enums\TaskBehavior;
use App\Enums\TaskDateMode;
use App\Enums\TaskPriority;
use App\Models\Task;
use App\Models\TaskCategory;
use App\Models\TaskStatus;
use App\Models\Wedding;
use App\Models\WeddingEvent;
use App\Models\WeddingMember;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlanningSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_planning_models_expose_expected_casts_and_relationships(): void
    {
        $wedding = Wedding::factory()->create();
        $category = TaskCategory::factory()->for($wedding)->create();
        $status = TaskStatus::factory()->for($wedding)->create(['behavior' => TaskBehavior::InProgress]);
        $event = WeddingEvent::factory()->for($wedding)->create();
        $assignee = WeddingMember::factory()->for($wedding)->create();
        $task = Task::factory()->for($wedding)->create([
            'task_category_id' => $category->id,
            'task_status_id' => $status->id,
            'wedding_event_id' => $event->id,
            'assignee_membership_id' => $assignee->id,
            'priority' => TaskPriority::High,
            'date_mode' => TaskDateMode::Manual,
        ]);

        $this->assertSame(TaskBehavior::InProgress, $status->behavior);
        $this->assertSame(TaskPriority::High, $task->priority);
        $this->assertSame(TaskDateMode::Manual, $task->date_mode);
        $this->assertTrue($task->category->is($category));
        $this->assertTrue($task->status->is($status));
        $this->assertTrue($task->event->is($event));
        $this->assertTrue($task->assignee->is($assignee));
    }

    public function test_database_allows_only_one_primary_event_per_wedding(): void
    {
        $wedding = Wedding::factory()->create();
        WeddingEvent::factory()->for($wedding)->primary()->create();

        $this->expectException(QueryException::class);
        WeddingEvent::factory()->for($wedding)->primary()->create();
    }

    public function test_database_rejects_cross_wedding_task_relationships(): void
    {
        $first = Wedding::factory()->create();
        $second = Wedding::factory()->create();
        $category = TaskCategory::factory()->for($second)->create();
        $status = TaskStatus::factory()->for($first)->create();

        $this->expectException(QueryException::class);
        Task::factory()->for($first)->create([
            'task_category_id' => $category->id,
            'task_status_id' => $status->id,
        ]);
    }

    public function test_each_wedding_receives_independent_indonesian_planning_defaults(): void
    {
        $this->seed();
        $first = Wedding::factory()->create();
        $second = Wedding::factory()->create();

        app(SeedWeddingPlanningDefaultsAction::class)->execute($first);
        app(SeedWeddingPlanningDefaultsAction::class)->execute($second);

        $this->assertSame(4, $first->taskCategories()->count());
        $this->assertSame(4, $first->taskStatuses()->count());
        $this->assertDatabaseHas('task_statuses', [
            'wedding_id' => $first->id,
            'name' => 'Selesai',
            'behavior' => TaskBehavior::Completed->value,
        ]);

        $first->taskCategories()->where('name', 'Persiapan Utama')->update(['name' => 'Khusus Wedding Pertama']);
        $this->assertDatabaseHas('task_categories', [
            'wedding_id' => $second->id,
            'name' => 'Persiapan Utama',
        ]);
    }
}
