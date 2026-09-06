<?php

namespace Tests\Feature\Workspace;

use App\Actions\Weddings\CloneWeddingTemplateAction;
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
}
