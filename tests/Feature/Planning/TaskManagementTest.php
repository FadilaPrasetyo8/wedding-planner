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
use Tests\TestCase;

class TaskManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_and_editor_can_create_tasks_while_viewer_cannot(): void
    {
        [$wedding, $owner] = $this->workspace();
        $editor = $this->member($wedding, 'editor');
        $viewer = $this->member($wedding, 'viewer');
        $category = $wedding->taskCategories()->firstOrFail();
        $status = $wedding->taskStatuses()->where('behavior', TaskBehavior::Todo)->firstOrFail();
        $payload = [
            'title' => 'Pesan gedung',
            'task_category_id' => $category->uuid,
            'task_status_id' => $status->uuid,
            'priority' => 'high',
            'date_mode' => 'manual',
            'start_date' => '2027-01-01',
            'due_date' => '2027-01-15',
        ];

        $this->actingAs($owner)->post(route('weddings.tasks.store', $wedding), $payload)->assertRedirect();
        $this->actingAs($editor)->post(route('weddings.tasks.store', $wedding), [...$payload, 'title' => 'Pilih katering'])->assertRedirect();
        $this->actingAs($viewer)->post(route('weddings.tasks.store', $wedding), [...$payload, 'title' => 'Tidak boleh'])->assertForbidden();
        $this->assertSame(2, $wedding->tasks()->count());
    }

    public function test_task_references_and_assignee_must_belong_to_same_wedding_and_be_active(): void
    {
        [$wedding, $owner] = $this->workspace();
        [$other] = $this->workspace();
        $foreignCategory = $other->taskCategories()->firstOrFail();
        $status = $wedding->taskStatuses()->firstOrFail();
        $inactive = WeddingMember::factory()->for($wedding)->inactive()->create();

        $this->actingAs($owner)->post(route('weddings.tasks.store', $wedding), [
            'title' => 'Referensi silang',
            'task_category_id' => $foreignCategory->uuid,
            'task_status_id' => $status->uuid,
            'assignee_membership_id' => $inactive->id,
            'priority' => 'medium',
            'date_mode' => 'manual',
            'start_date' => '2027-01-10',
            'due_date' => '2027-01-01',
        ])->assertSessionHasErrors(['task_category_id', 'assignee_membership_id', 'due_date']);
    }

    public function test_completed_status_manages_completed_at_and_relative_dates_use_primary_event(): void
    {
        [$wedding, $owner] = $this->workspace();
        $category = $wedding->taskCategories()->firstOrFail();
        $todo = $wedding->taskStatuses()->where('behavior', TaskBehavior::Todo)->firstOrFail();
        $completed = $wedding->taskStatuses()->where('behavior', TaskBehavior::Completed)->firstOrFail();

        $this->actingAs($owner)->post(route('weddings.tasks.store', $wedding), [
            'title' => 'Tugas relatif',
            'task_category_id' => $category->uuid,
            'task_status_id' => $completed->uuid,
            'priority' => 'urgent',
            'date_mode' => 'template_relative',
            'relative_start_offset' => -20,
            'relative_due_offset' => -10,
        ])->assertRedirect();

        $task = $wedding->tasks()->firstOrFail();
        $this->assertNotNull($task->completed_at);
        $this->assertSame('2027-05-31', $task->start_date->toDateString());
        $this->assertSame('2027-06-10', $task->due_date->toDateString());

        $this->actingAs($owner)->patch(route('weddings.tasks.update', [$wedding, $task]), [
            'title' => $task->title,
            'task_category_id' => $category->uuid,
            'task_status_id' => $todo->uuid,
            'priority' => 'urgent',
            'date_mode' => 'template_relative',
            'relative_start_offset' => -20,
            'relative_due_offset' => -10,
        ])->assertRedirect();
        $this->assertNull($task->refresh()->completed_at);
    }

    public function test_viewer_can_read_task_detail_but_cannot_mutate_it(): void
    {
        [$wedding] = $this->workspace();
        $viewer = $this->member($wedding, 'viewer');
        $task = Task::factory()->for($wedding)->create([
            'title' => 'Detail hanya lihat',
            'task_category_id' => $wedding->taskCategories()->firstOrFail()->id,
            'task_status_id' => $wedding->taskStatuses()->firstOrFail()->id,
        ]);

        $this->actingAs($viewer)->get(route('weddings.tasks.show', [$wedding, $task]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('weddings/planning/show')
                ->where('task.title', 'Detail hanya lihat')
                ->where('canEdit', false));
        $this->actingAs($viewer)->delete(route('weddings.tasks.destroy', [$wedding, $task]))
            ->assertForbidden();
    }

    public function test_cross_wedding_nested_task_returns_not_found(): void
    {
        [$wedding, $owner] = $this->workspace();
        [$other] = $this->workspace();
        $task = Task::factory()->for($other)->create([
            'task_category_id' => $other->taskCategories()->firstOrFail()->id,
            'task_status_id' => $other->taskStatuses()->firstOrFail()->id,
        ]);

        $this->actingAs($owner)->delete(route('weddings.tasks.destroy', [$wedding, $task]))->assertNotFound();
    }

    /** @return array{Wedding, User} */
    private function workspace(): array
    {
        $wedding = Wedding::factory()->create(['wedding_date' => '2027-06-20']);
        app(SeedWeddingPlanningDefaultsAction::class)->execute($wedding);
        WeddingEvent::factory()->for($wedding)->primary()->create(['event_date' => '2027-06-20']);
        $owner = User::factory()->create();
        WeddingMember::factory()->for($wedding)->for($owner)->owner()->create();

        return [$wedding, $owner];
    }

    private function member(Wedding $wedding, string $role): User
    {
        $user = User::factory()->create();
        WeddingMember::factory()->for($wedding)->for($user)->create(['role' => $role]);

        return $user;
    }
}
