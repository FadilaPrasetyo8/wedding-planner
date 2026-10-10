<?php

namespace Tests\Feature\Logistics;

use App\Actions\Weddings\CreateWeddingAction;
use App\Models\User;
use App\Models\WeddingMember;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChecklistDropdownPayloadTest extends TestCase
{
    use RefreshDatabase;

    public function test_administration_status_dropdown_payload_marks_overdue_item_done(): void
    {
        $owner = User::factory()->create();
        $wedding = app(CreateWeddingAction::class)->execute($owner, [
            'name' => 'Pernikahan Test',
            'partner_one_name' => 'Ayu',
            'partner_two_name' => 'Bima',
            'wedding_date' => now()->addYear()->toDateString(),
            'timezone' => 'Asia/Jakarta',
            'initial_event_name' => 'Resepsi',
        ]);

        $editor = User::factory()->create();
        WeddingMember::factory()->for($wedding)->for($editor, 'user')->create();

        $openStatus = $wedding->checklistStatuses()->where('type', 'administration')->where('is_completed', false)->orderBy('sort_order')->firstOrFail();
        $doneStatus = $wedding->checklistStatuses()->where('type', 'administration')->where('is_completed', true)->firstOrFail();

        $item = $wedding->checklistItems()->create([
            'type' => 'administration',
            'name' => 'Surat nikah',
            'checklist_status_id' => $openStatus->id,
            'quantity' => 1,
            'deadline' => now()->subDays(3)->toDateString(),
        ]);

        // Payload sent by ItemStatusForm for administration: no purchase_url field.
        $this->actingAs($editor)->patch(route('weddings.checklist-items.update', [
            'wedding' => $wedding, 'type' => 'administration', 'checklist_item' => $item,
        ]), [
            'checklist_status_uuid' => $doneStatus->uuid,
            'name' => 'Surat nikah',
            'quantity' => 1,
            'deadline' => $item->deadline->toDateString(),
            'checklist_category_uuid' => '',
            'checklist_group_uuid' => '',
            'assignee_member_id' => '',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $fresh = $item->fresh();
        $this->assertSame($doneStatus->id, $fresh->checklist_status_id, 'Status must be saved.');
        $this->assertNotNull($fresh->completed_at, 'completed_at must be stamped.');
        $this->assertFalse($fresh->isOverdue(), 'Item must not be overdue after completion.');
    }
}
