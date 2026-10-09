<?php

namespace Tests\Feature\Logistics;

use App\Actions\Weddings\CreateWeddingAction;
use App\Models\ChecklistItem;
use App\Models\User;
use App\Models\Wedding;
use App\Models\WeddingMember;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChecklistTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private User $editor;

    private User $viewer;

    private Wedding $wedding;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create();
        $this->wedding = app(CreateWeddingAction::class)->execute($this->owner, [
            'name' => 'Pernikahan Test',
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

    private function item(string $type, array $overrides = []): ChecklistItem
    {
        $status = $this->wedding->checklistStatuses()
            ->where('type', $type)
            ->where('is_completed', false)
            ->orderBy('sort_order')
            ->first();

        return $this->wedding->checklistItems()->create(array_merge([
            'type' => $type,
            'name' => 'Item Uji',
            'checklist_group_id' => null,
            'checklist_category_id' => null,
            'checklist_status_id' => $status?->id,
            'assignee_member_id' => null,
            'notes' => null,
            'quantity' => 2,
            'estimated_cost' => 1_000_000,
            'actual_cost' => null,
            'deadline' => now()->addMonth()->toDateString(),
            'completed_at' => null,
            'purchase_url' => null,
        ], $overrides));
    }

    public function test_index_requires_membership_and_returns_summary(): void
    {
        $this->item('seserahan', ['name' => 'Kalung emas', 'estimated_cost' => 5_000_000, 'quantity' => 1]);
        $this->item('seserahan', ['name' => 'Sarung batik', 'estimated_cost' => 500_000, 'quantity' => 4]);
        $this->item('administration', ['name' => 'Surat nikah']);

        $response = $this->actingAs($this->viewer)
            ->get(route('weddings.checklists.index', ['wedding' => $this->wedding, 'type' => 'seserahan']))
            ->assertOk();

        $page = $response->viewData('page')['props'];
        $names = collect($page['items']['data'])->pluck('name')->all();
        $this->assertContains('Kalung emas', $names);
        $this->assertNotContains('Surat nikah', $names);
        $this->assertSame(7_000_000, $page['summary']['estimatedTotal']);
        $this->assertSame(2, $page['summary']['itemCount']);
        $this->assertFalse($page['canEdit']);

        // Non-member cannot read another wedding's checklist.
        $outsider = User::factory()->create();
        $this->actingAs($outsider)
            ->get(route('weddings.checklists.index', ['wedding' => $this->wedding, 'type' => 'seserahan']))
            ->assertForbidden();

        // Unknown type is rejected.
        $this->actingAs($this->owner)
            ->get(route('weddings.checklists.index', ['wedding' => $this->wedding, 'type' => 'unknown']))
            ->assertNotFound();
    }

    public function test_editor_can_create_item_with_uuid_references(): void
    {
        $status = $this->wedding->checklistStatuses()->where('type', 'seserahan')->orderBy('sort_order')->first();
        $category = $this->wedding->checklistCategories()->where('type', 'seserahan')->first();

        $this->actingAs($this->editor)
            ->post(route('weddings.checklist-items.store', ['wedding' => $this->wedding, 'type' => 'seserahan']), [
                'name' => 'Kalung emas',
                'quantity' => 1,
                'estimated_cost' => 5_000_000,
                'deadline' => now()->addMonth()->toDateString(),
                'checklist_category_uuid' => $category->uuid,
                'checklist_status_uuid' => $status->uuid,
                'purchase_url' => 'https://toko.example/kalung',
            ])->assertRedirect()->assertSessionHas('success');

        $item = $this->wedding->checklistItems()->where('name', 'Kalung emas')->first();
        $this->assertNotNull($item);
        $this->assertSame($category->id, $item->checklist_category_id);
        $this->assertSame($status->id, $item->checklist_status_id);
        $this->assertSame('https://toko.example/kalung', $item->purchase_url);
        $this->assertNull($item->completed_at, 'Non-completed status must not stamp completed_at.');
    }

    public function test_purchase_url_is_prohibited_on_administration(): void
    {
        $status = $this->wedding->checklistStatuses()->where('type', 'administration')->first();

        $this->actingAs($this->editor)
            ->post(route('weddings.checklist-items.store', ['wedding' => $this->wedding, 'type' => 'administration']), [
                'name' => 'Surat nikah',
                'quantity' => 2,
                'estimated_cost' => 150_000,
                'checklist_status_uuid' => $status->uuid,
                'purchase_url' => 'https://toko.example/nikah',
            ])->assertRedirect()->assertSessionHasErrors('purchase_url');

        $this->assertSame(0, $this->wedding->checklistItems()->where('type', 'administration')->count());
    }

    public function test_uuid_from_another_wedding_is_rejected(): void
    {
        $otherOwner = User::factory()->create();
        $otherWedding = app(CreateWeddingAction::class)->execute($otherOwner, [
            'name' => 'Wedding Lain',
            'partner_one_name' => 'Cinta',
            'partner_two_name' => 'Dewa',
            'wedding_date' => now()->addYear()->toDateString(),
            'timezone' => 'Asia/Jakarta',
            'initial_event_name' => 'Resepsi',
        ]);
        $otherCategory = $otherWedding->checklistCategories()->where('type', 'seserahan')->first();

        $this->actingAs($this->editor)
            ->post(route('weddings.checklist-items.store', ['wedding' => $this->wedding, 'type' => 'seserahan']), [
                'name' => 'Item curang',
                'quantity' => 1,
                'checklist_category_uuid' => $otherCategory->uuid,
            ])->assertRedirect()->assertSessionHasErrors('checklist_category_uuid');
    }

    public function test_first_completion_stamps_completed_at_and_retains_it(): void
    {
        $item = $this->item('seserahan');
        $completedStatus = $this->wedding->checklistStatuses()
            ->where('type', 'seserahan')->where('is_completed', true)->first();

        $this->actingAs($this->editor)
            ->patch(route('weddings.checklist-items.update', [
                'wedding' => $this->wedding, 'type' => 'seserahan', 'checklist_item' => $item,
            ]), [
                'name' => $item->name,
                'quantity' => $item->quantity,
                'estimated_cost' => $item->estimated_cost,
                'checklist_status_uuid' => $completedStatus->uuid,
            ])->assertRedirect()->assertSessionHas('success');

        $item->refresh();
        $firstStamp = $item->completed_at;
        $this->assertNotNull($firstStamp, 'First completion must stamp completed_at.');

        // Updating again with the same completed status retains the original stamp.
        $this->travel(1, 'hours');

        $this->actingAs($this->editor)
            ->patch(route('weddings.checklist-items.update', [
                'wedding' => $this->wedding, 'type' => 'seserahan', 'checklist_item' => $item,
            ]), [
                'name' => $item->name.' (revisi)',
                'quantity' => $item->quantity,
                'estimated_cost' => $item->estimated_cost,
                'checklist_status_uuid' => $completedStatus->uuid,
            ])->assertRedirect();

        $item->refresh();
        $this->assertSame($firstStamp->toDateTimeString(), $item->completed_at?->toDateTimeString(), 'Re-saving a completed item must retain the first completion timestamp.');

        // Moving back to an open status clears the stamp.
        $openStatus = $this->wedding->checklistStatuses()
            ->where('type', 'seserahan')->where('is_completed', false)->orderBy('sort_order')->first();
        $this->actingAs($this->editor)
            ->patch(route('weddings.checklist-items.update', [
                'wedding' => $this->wedding, 'type' => 'seserahan', 'checklist_item' => $item,
            ]), [
                'name' => $item->name,
                'quantity' => $item->quantity,
                'estimated_cost' => $item->estimated_cost,
                'checklist_status_uuid' => $openStatus->uuid,
            ])->assertRedirect();

        $item->refresh();
        $this->assertNull($item->completed_at, 'Returning to an open status must clear completed_at.');
    }

    public function test_summary_counts_overdue_and_totals(): void
    {
        $completedStatus = $this->wedding->checklistStatuses()
            ->where('type', 'seserahan')->where('is_completed', true)->first();

        $this->item('seserahan', ['name' => 'Terlambat', 'deadline' => now()->subWeek()->toDateString()]);
        $this->item('seserahan', [
            'name' => 'Selesai tepat waktu',
            'deadline' => now()->subWeek()->toDateString(),
            'checklist_status_id' => $completedStatus->id,
            'completed_at' => now()->subWeek(),
            'actual_cost' => 750_000,
        ]);
        $this->item('seserahan', ['name' => 'Masa depan', 'deadline' => now()->addMonth()->toDateString()]);

        $response = $this->actingAs($this->owner)
            ->get(route('weddings.checklists.index', ['wedding' => $this->wedding, 'type' => 'seserahan']))
            ->assertOk();

        $summary = $response->viewData('page')['props']['summary'];
        $this->assertSame(3, $summary['itemCount']);
        $this->assertSame(1, $summary['completedCount']);
        $this->assertSame(1, $summary['overdueCount'], 'Only the uncompleted past-deadline item is overdue.');
        $this->assertSame(6_000_000, $summary['estimatedTotal']);
        $this->assertSame(1_500_000, $summary['actualTotal']);
    }

    public function test_viewer_cannot_mutate_and_reference_protection(): void
    {
        $item = $this->item('seserahan');

        $this->actingAs($this->viewer)
            ->post(route('weddings.checklist-items.store', ['wedding' => $this->wedding, 'type' => 'seserahan']), [
                'name' => 'Dilarang',
                'quantity' => 1,
            ])->assertForbidden();

        $this->actingAs($this->viewer)
            ->delete(route('weddings.checklist-items.destroy', [
                'wedding' => $this->wedding, 'type' => 'seserahan', 'checklist_item' => $item,
            ]))->assertForbidden();
        $this->assertNotNull($item->fresh());

        // A status in use cannot be deleted; an unused one can.
        $inUse = $this->wedding->checklistStatuses()->where('type', 'seserahan')->first();
        $this->actingAs($this->editor)
            ->delete(route('weddings.checklist-references.destroy', [
                'wedding' => $this->wedding, 'type' => 'seserahan', 'reference' => 'statuses', 'uuid' => $inUse->uuid,
            ]))->assertRedirect()->assertSessionHasErrors('reference');
        $this->assertNotNull($inUse->fresh());

        $unused = $this->wedding->checklistStatuses()->create([
            'type' => 'seserahan',
            'name' => 'Status Sisa',
            'color' => 'slate',
            'is_completed' => false,
            'sort_order' => 90,
        ]);
        $this->actingAs($this->editor)
            ->delete(route('weddings.checklist-references.destroy', [
                'wedding' => $this->wedding, 'type' => 'seserahan', 'reference' => 'statuses', 'uuid' => $unused->uuid,
            ]))->assertRedirect()->assertSessionHas('success');
        $this->assertNull($unused->fresh());
    }

    public function test_editor_can_manage_references(): void
    {
        $this->actingAs($this->editor)
            ->post(route('weddings.checklist-references.store', [
                'wedding' => $this->wedding, 'type' => 'seserahan', 'reference' => 'statuses',
            ]), [
                'type' => 'seserahan',
                'name' => 'Dibungkus',
                'color' => 'clay',
                'is_completed' => false,
            ])->assertRedirect()->assertSessionHas('success');

        $this->assertDatabaseHas('checklist_statuses', [
            'wedding_id' => $this->wedding->id,
            'type' => 'seserahan',
            'name' => 'Dibungkus',
        ]);

        // Reference type must match the module type.
        $this->actingAs($this->editor)
            ->post(route('weddings.checklist-references.store', [
                'wedding' => $this->wedding, 'type' => 'seserahan', 'reference' => 'statuses',
            ]), [
                'type' => 'administration',
                'name' => 'Salah tipe',
                'color' => 'slate',
                'is_completed' => false,
            ])->assertRedirect()->assertSessionHasErrors('type');
    }

    public function test_checklist_is_isolated_per_wedding(): void
    {
        $otherOwner = User::factory()->create();
        $otherWedding = app(CreateWeddingAction::class)->execute($otherOwner, [
            'name' => 'Wedding Lain',
            'partner_one_name' => 'Cinta',
            'partner_two_name' => 'Dewa',
            'wedding_date' => now()->addYear()->toDateString(),
            'timezone' => 'Asia/Jakarta',
            'initial_event_name' => 'Resepsi',
        ]);
        $otherItem = $otherWedding->checklistItems()->create([
            'type' => 'seserahan',
            'name' => 'Item Wedding Lain',
            'quantity' => 1,
            'estimated_cost' => 1,
            'actual_cost' => null,
            'deadline' => null,
            'completed_at' => null,
            'purchase_url' => null,
        ]);

        // Update/destroy scoped to the wrong wedding is 404, not silent success.
        $this->actingAs($this->editor)
            ->patch(route('weddings.checklist-items.update', [
                'wedding' => $this->wedding, 'type' => 'seserahan', 'checklist_item' => $otherItem,
            ]), ['name' => 'Peretas', 'quantity' => 1])->assertNotFound();

        $this->actingAs($this->editor)
            ->delete(route('weddings.checklist-items.destroy', [
                'wedding' => $this->wedding, 'type' => 'seserahan', 'checklist_item' => $otherItem,
            ]))->assertNotFound();
        $this->assertNotNull($otherItem->fresh());
    }
}
