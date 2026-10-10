<?php

namespace Tests\Feature\Exports;

use App\Actions\Weddings\CreateWeddingAction;
use App\Models\User;
use App\Models\Wedding;
use App\Models\WeddingMember;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WeddingExportAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private Wedding $wedding;

    private User $owner;

    private User $editor;

    private User $viewer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create();
        $this->wedding = app(CreateWeddingAction::class)->execute($this->owner, [
            'name' => 'Pernikahan Export',
            'partner_one_name' => 'Ayu',
            'partner_two_name' => 'Bima',
            'wedding_date' => now()->addYear()->toDateString(),
            'timezone' => 'Asia/Jakarta',
            'initial_event_name' => 'Resepsi',
        ]);
        $this->editor = User::factory()->create();
        WeddingMember::factory()->for($this->wedding)->for($this->editor, 'user')->create();
        $this->viewer = User::factory()->create();
        WeddingMember::factory()->viewer()->for($this->wedding)->for($this->viewer, 'user')->create();
    }

    public function test_owner_and_editor_can_export_but_viewer_cannot(): void
    {
        $this->assertTrue($this->owner->can('export', $this->wedding));
        $this->assertTrue($this->editor->can('export', $this->wedding));
        $this->assertFalse($this->viewer->can('export', $this->wedding));

        $this->actingAs($this->owner)
            ->get(route('weddings.exports.budget', $this->wedding))
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $this->actingAs($this->editor)
            ->get(route('weddings.exports.guests', $this->wedding))
            ->assertOk();

        $this->actingAs($this->viewer)
            ->get(route('weddings.exports.budget', $this->wedding))
            ->assertForbidden();
    }

    public function test_outsider_cannot_export_a_wedding(): void
    {
        $outsider = User::factory()->create();

        $this->actingAs($outsider)
            ->get(route('weddings.exports.planning', $this->wedding))
            ->assertForbidden();
    }

    public function test_each_selected_export_route_streams_a_csv_for_an_editor(): void
    {
        $event = $this->wedding->events()->firstOrFail();
        $routes = [
            route('weddings.exports.budget', $this->wedding),
            route('weddings.exports.guests', $this->wedding),
            route('weddings.exports.planning', $this->wedding),
            route('weddings.exports.checklist', [$this->wedding, 'seserahan']),
            route('weddings.exports.rundown', [$this->wedding, 'event' => $event->uuid]),
        ];

        foreach ($routes as $route) {
            $this->actingAs($this->editor)
                ->get($route)
                ->assertOk()
                ->assertHeader('content-type', 'text/csv; charset=UTF-8');
        }
    }

    public function test_export_filters_reject_references_from_another_wedding(): void
    {
        $otherOwner = User::factory()->create();
        $otherWedding = app(CreateWeddingAction::class)->execute($otherOwner, [
            'name' => 'Pernikahan Lain',
            'partner_one_name' => 'Citra',
            'partner_two_name' => 'Dewa',
            'wedding_date' => now()->addYear()->toDateString(),
            'timezone' => 'Asia/Jakarta',
            'initial_event_name' => 'Akad',
        ]);

        $this->actingAs($this->editor)
            ->get(route('weddings.exports.budget', [
                'wedding' => $this->wedding,
                'category' => $otherWedding->budgetCategories()->firstOrFail()->uuid,
            ]))
            ->assertSessionHasErrors('category');

        $this->actingAs($this->editor)
            ->get(route('weddings.exports.rundown', [
                'wedding' => $this->wedding,
                'event' => $otherWedding->events()->firstOrFail()->uuid,
            ]))
            ->assertSessionHasErrors('event');
    }
}
