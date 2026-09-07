<?php

namespace Tests\Feature\Planning;

use App\Actions\Weddings\SeedWeddingPlanningDefaultsAction;
use App\Enums\TaskBehavior;
use App\Models\Task;
use App\Models\User;
use App\Models\Wedding;
use App\Models\WeddingMember;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskConfigurationTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_and_editor_can_rename_configuration_but_status_behavior_is_immutable(): void
    {
        [$wedding, $owner] = $this->workspace();
        $editor = $this->member($wedding, 'editor');
        $viewer = $this->member($wedding, 'viewer');
        $category = $wedding->taskCategories()->firstOrFail();
        $status = $wedding->taskStatuses()->where('behavior', TaskBehavior::Todo)->firstOrFail();

        $this->actingAs($owner)->patch(route('weddings.task-categories.update', [$wedding, $category]), [
            'name' => 'Persiapan Inti', 'color' => 'rose', 'sort_order' => 5, 'is_active' => true,
        ])->assertRedirect();
        $this->actingAs($editor)->patch(route('weddings.task-statuses.update', [$wedding, $status]), [
            'name' => 'Akan Dikerjakan', 'color' => 'clay', 'sort_order' => 5, 'is_active' => true,
            'behavior' => TaskBehavior::Completed->value,
        ])->assertRedirect();
        $this->actingAs($viewer)->patch(route('weddings.task-categories.update', [$wedding, $category]), [
            'name' => 'Tidak boleh', 'color' => 'rose', 'sort_order' => 5, 'is_active' => true,
        ])->assertForbidden();

        $this->assertSame(TaskBehavior::Todo, $status->refresh()->behavior);
    }

    public function test_owner_and_editor_can_toggle_configuration_active_state(): void
    {
        [$wedding, $owner] = $this->workspace();
        $editor = $this->member($wedding, 'editor');
        $category = $wedding->taskCategories()->firstOrFail();
        $status = $wedding->taskStatuses()->firstOrFail();

        $this->actingAs($owner)->patch(route('weddings.task-categories.update', [$wedding, $category]), [
            'name' => $category->name,
            'color' => $category->color,
            'sort_order' => $category->sort_order,
            'is_active' => false,
        ])->assertRedirect();
        $this->actingAs($editor)->patch(route('weddings.task-statuses.update', [$wedding, $status]), [
            'name' => $status->name,
            'color' => $status->color,
            'sort_order' => $status->sort_order,
            'is_active' => false,
        ])->assertRedirect();

        $this->assertFalse($category->refresh()->is_active);
        $this->assertFalse($status->refresh()->is_active);
    }

    public function test_owner_and_editor_can_create_wedding_scoped_configuration(): void
    {
        [$wedding, $owner] = $this->workspace();
        $editor = $this->member($wedding, 'editor');

        $this->actingAs($owner)->post(route('weddings.task-categories.store', $wedding), [
            'name' => 'Busana',
            'color' => 'rose',
            'sort_order' => 10,
            'is_active' => true,
        ])->assertRedirect();
        $inProgress = $wedding->taskStatuses()->where('behavior', TaskBehavior::InProgress)->firstOrFail();
        $this->actingAs($owner)->delete(route('weddings.task-statuses.destroy', [$wedding, $inProgress]))
            ->assertRedirect();
        $this->actingAs($editor)->post(route('weddings.task-statuses.store', $wedding), [
            'name' => 'Menunggu Konfirmasi',
            'color' => 'clay',
            'behavior' => TaskBehavior::InProgress->value,
            'sort_order' => 10,
            'is_active' => true,
        ])->assertRedirect();

        $this->assertDatabaseHas('task_categories', ['wedding_id' => $wedding->id, 'name' => 'Busana']);
        $this->assertDatabaseHas('task_statuses', [
            'wedding_id' => $wedding->id,
            'name' => 'Menunggu Konfirmasi',
            'behavior' => TaskBehavior::InProgress->value,
        ]);
    }

    public function test_viewer_can_open_configuration_read_only_but_cannot_create_it(): void
    {
        [$wedding] = $this->workspace();
        $viewer = $this->member($wedding, 'viewer');

        $this->actingAs($viewer)->get(route('weddings.planning.configuration', $wedding))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('weddings/planning/configuration')
                ->where('canEdit', false)
                ->has('categories', 4)
                ->has('statuses', 4));
        $this->actingAs($viewer)->post(route('weddings.task-categories.store', $wedding), [
            'name' => 'Tidak boleh',
            'color' => 'rose',
            'sort_order' => 10,
            'is_active' => true,
        ])->assertForbidden();
    }

    public function test_used_configuration_cannot_be_deleted(): void
    {
        [$wedding, $owner] = $this->workspace();
        $category = $wedding->taskCategories()->firstOrFail();
        $status = $wedding->taskStatuses()->firstOrFail();
        Task::factory()->for($wedding)->create([
            'task_category_id' => $category->id,
            'task_status_id' => $status->id,
        ]);

        $this->actingAs($owner)->delete(route('weddings.task-categories.destroy', [$wedding, $category]))
            ->assertSessionHasErrors('category');
        $this->actingAs($owner)->delete(route('weddings.task-statuses.destroy', [$wedding, $status]))
            ->assertSessionHasErrors('status');
        $this->assertDatabaseHas('task_categories', ['id' => $category->id]);
        $this->assertDatabaseHas('task_statuses', ['id' => $status->id]);
    }

    public function test_nested_configuration_routes_reject_another_weddings_records(): void
    {
        [$wedding, $owner] = $this->workspace();
        [$other] = $this->workspace();

        $this->actingAs($owner)->patch(route('weddings.task-categories.update', [
            $wedding,
            $other->taskCategories()->firstOrFail(),
        ]), [
            'name' => 'Lintas ruang',
            'color' => 'rose',
            'sort_order' => 5,
            'is_active' => true,
        ])->assertNotFound();

        $this->actingAs($owner)->delete(route('weddings.task-statuses.destroy', [
            $wedding,
            $other->taskStatuses()->firstOrFail(),
        ]))->assertNotFound();
    }

    /** @return array{Wedding, User} */
    private function workspace(): array
    {
        $wedding = Wedding::factory()->create();
        app(SeedWeddingPlanningDefaultsAction::class)->execute($wedding);
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
