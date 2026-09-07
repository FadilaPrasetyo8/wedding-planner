<?php

namespace Tests\Feature\Workspace;

use App\Actions\Weddings\CloneWeddingTemplateAction;
use App\Enums\TaskDateMode;
use App\Enums\TaskPriority;
use App\Enums\WeddingMemberRole;
use App\Models\Template;
use App\Models\User;
use App\Models\Wedding;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CreateWeddingTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_open_onboarding_with_active_templates(): void
    {
        $this->seed();
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('weddings.create'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('weddings/create')
                ->has('templates', 2)
                ->where('templates.0.items.0.type', 'event'));
    }

    public function test_authenticated_user_creates_multiple_independent_weddings_as_owner(): void
    {
        $this->seed();
        $user = User::factory()->create();
        $template = Template::query()->where('slug', 'bismillah-nikah')->firstOrFail();

        foreach (['Pernikahan Pertama', 'Pernikahan Kedua'] as $name) {
            $this->actingAs($user)->post(route('weddings.store'), [
                'name' => $name,
                'partner_one_name' => 'Alya',
                'partner_two_name' => 'Bima',
                'wedding_date' => '2027-06-20',
                'planning_start_date' => '2026-09-04',
                'timezone' => 'Asia/Jakarta',
                'target_budget' => 50000000,
                'initial_event_name' => 'Akad',
                'template_id' => $template->id,
            ])->assertRedirect();
        }

        $this->assertSame(2, Wedding::query()->count());
        $this->assertSame(2, $user->weddingMemberships()->where('role', WeddingMemberRole::Owner)->count());
        $this->assertSame(4, Wedding::query()->withCount('events')->get()->sum('events_count'));
    }

    public function test_wedding_creation_validates_dates_and_whole_rupiah_budget(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('weddings.store'), [
            'name' => 'Pernikahan',
            'partner_one_name' => 'Alya',
            'partner_two_name' => 'Bima',
            'wedding_date' => '2027-01-01',
            'planning_start_date' => '2027-02-01',
            'timezone' => 'Invalid/Zone',
            'target_budget' => -1,
            'initial_event_name' => 'Akad',
        ])->assertSessionHasErrors(['planning_start_date', 'timezone', 'target_budget']);

        $this->assertDatabaseCount('weddings', 0);
    }

    public function test_wedding_creation_rolls_back_when_template_cloning_fails(): void
    {
        $this->seed();
        $user = User::factory()->create();
        $template = Template::query()->firstOrFail();
        $cloner = $this->mock(CloneWeddingTemplateAction::class);
        $cloner->shouldReceive('execute')->once()->andThrow(new \RuntimeException('clone failed'));

        try {
            $this->actingAs($user)->post(route('weddings.store'), [
                'name' => 'Pernikahan Alya dan Bima',
                'partner_one_name' => 'Alya',
                'partner_two_name' => 'Bima',
                'wedding_date' => '2027-06-20',
                'timezone' => 'Asia/Jakarta',
                'initial_event_name' => 'Akad',
                'template_id' => $template->id,
            ]);
        } catch (\RuntimeException $exception) {
            $this->assertSame('clone failed', $exception->getMessage());
        }

        $this->assertDatabaseCount('weddings', 0);
        $this->assertDatabaseCount('wedding_members', 0);
        $this->assertDatabaseCount('wedding_events', 0);
        $this->assertDatabaseCount('activity_logs', 0);
    }

    public function test_template_clones_are_independent_between_weddings(): void
    {
        $this->seed();
        $template = Template::query()->where('slug', 'inti-acara')->firstOrFail();
        $users = User::factory(2)->create();

        foreach ($users as $index => $user) {
            $this->actingAs($user)->post(route('weddings.store'), [
                'name' => 'Wedding '.$index,
                'partner_one_name' => 'Alya', 'partner_two_name' => 'Bima',
                'wedding_date' => '2027-06-20', 'timezone' => 'Asia/Jakarta',
                'initial_event_name' => 'Akad', 'template_id' => $template->id,
            ]);
        }

        $first = Wedding::query()->orderBy('id')->firstOrFail();
        $second = Wedding::query()->orderByDesc('id')->firstOrFail();
        $first->events()->where('name', 'Acara Utama')->update(['name' => 'Acara Diubah']);

        $this->assertDatabaseHas('wedding_events', ['wedding_id' => $second->id, 'name' => 'Acara Utama']);
        $this->assertDatabaseHas('template_items', ['template_section_id' => $template->sections()->firstOrFail()->id]);
    }

    public function test_template_tasks_clone_with_relative_dates_and_remain_independent(): void
    {
        $template = Template::query()->create([
            'name' => 'Rencana Ringkas',
            'slug' => 'rencana-ringkas',
            'is_active' => true,
        ]);
        $section = $template->sections()->create(['name' => 'Persiapan', 'sort_order' => 1]);
        $section->items()->create([
            'type' => 'task',
            'payload' => [
                'title' => 'Pesan lokasi acara',
                'category' => 'Vendor & Lokasi',
                'status_behavior' => 'todo',
                'priority' => TaskPriority::High->value,
                'start_offset_days' => -120,
                'due_offset_days' => -90,
            ],
            'sort_order' => 1,
        ]);
        $users = User::factory(2)->create();

        foreach ($users as $index => $user) {
            $this->actingAs($user)->post(route('weddings.store'), [
                'name' => 'Wedding '.$index,
                'partner_one_name' => 'Alya',
                'partner_two_name' => 'Bima',
                'wedding_date' => '2027-06-20',
                'timezone' => 'Asia/Jakarta',
                'initial_event_name' => 'Akad',
                'template_id' => $template->id,
            ])->assertRedirect();
        }

        $first = Wedding::query()->orderBy('id')->firstOrFail();
        $second = Wedding::query()->orderByDesc('id')->firstOrFail();
        $firstTask = $first->tasks()->firstOrFail();

        $this->assertSame(TaskDateMode::TemplateRelative, $firstTask->date_mode);
        $this->assertSame('2027-02-20', $firstTask->start_date->toDateString());
        $this->assertSame('2027-03-22', $firstTask->due_date->toDateString());

        $firstTask->update(['title' => 'Judul khusus wedding pertama']);
        $this->assertDatabaseHas('tasks', [
            'wedding_id' => $second->id,
            'title' => 'Pesan lokasi acara',
        ]);
        $this->assertDatabaseHas('template_items', [
            'id' => $section->items()->firstOrFail()->id,
            'type' => 'task',
        ]);
    }

    public function test_invalid_template_task_rolls_back_wedding_creation(): void
    {
        $template = Template::query()->create([
            'name' => 'Template Tidak Valid',
            'slug' => 'template-tidak-valid',
            'is_active' => true,
        ]);
        $section = $template->sections()->create(['name' => 'Persiapan', 'sort_order' => 1]);
        $section->items()->create([
            'type' => 'task',
            'payload' => [
                'title' => 'Tanggal terbalik',
                'category' => 'Persiapan Utama',
                'status_behavior' => 'todo',
                'priority' => 'high',
                'start_offset_days' => -30,
                'due_offset_days' => -60,
            ],
            'sort_order' => 1,
        ]);
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('weddings.store'), [
            'name' => 'Wedding Gagal',
            'partner_one_name' => 'Alya',
            'partner_two_name' => 'Bima',
            'wedding_date' => '2027-06-20',
            'timezone' => 'Asia/Jakarta',
            'initial_event_name' => 'Akad',
            'template_id' => $template->id,
        ])->assertSessionHasErrors('template_id');

        $this->assertDatabaseMissing('weddings', ['name' => 'Wedding Gagal']);
    }
}
