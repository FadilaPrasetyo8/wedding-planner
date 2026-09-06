<?php

namespace Tests\Feature\Workspace;

use App\Enums\WeddingMemberRole;
use App\Models\User;
use App\Models\Wedding;
use App\Models\WeddingMember;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OwnershipTransferTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_transfers_ownership_to_existing_active_member_atomically(): void
    {
        $owner = User::factory()->create();
        $wedding = Wedding::factory()->create();
        $oldOwner = WeddingMember::factory()->for($wedding)->for($owner)->owner()->create();
        $newOwner = WeddingMember::factory()->for($wedding)->viewer()->create();

        $this->actingAs($owner)->post(route('weddings.ownership.transfer', $wedding), [
            'membership_id' => $newOwner->id,
        ])->assertRedirect();

        $this->assertSame(WeddingMemberRole::Editor, $oldOwner->fresh()->role);
        $this->assertSame(WeddingMemberRole::Owner, $newOwner->fresh()->role);
        $this->assertSame(1, $wedding->activeMemberships()->where('role', 'owner')->count());
    }

    public function test_transfer_rejects_outsider_inactive_member_and_non_owner_actor(): void
    {
        $owner = User::factory()->create();
        $wedding = Wedding::factory()->create();
        WeddingMember::factory()->for($wedding)->for($owner)->owner()->create();
        $editor = WeddingMember::factory()->for($wedding)->create();
        $inactive = WeddingMember::factory()->for($wedding)->inactive()->create();
        $otherWeddingMember = WeddingMember::factory()->create();

        $this->actingAs($editor->user)->post(route('weddings.ownership.transfer', $wedding), ['membership_id' => $editor->id])->assertForbidden();
        $this->actingAs($owner)->post(route('weddings.ownership.transfer', $wedding), ['membership_id' => $inactive->id])->assertSessionHasErrors('membership_id');
        $this->actingAs($owner)->post(route('weddings.ownership.transfer', $wedding), ['membership_id' => $otherWeddingMember->id])->assertSessionHasErrors('membership_id');
    }
}
