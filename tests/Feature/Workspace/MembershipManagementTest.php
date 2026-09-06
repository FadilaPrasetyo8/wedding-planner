<?php

namespace Tests\Feature\Workspace;

use App\Enums\WeddingMemberRole;
use App\Models\User;
use App\Models\Wedding;
use App\Models\WeddingMember;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MembershipManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_update_non_owner_role_and_remove_non_owner(): void
    {
        [$owner, $wedding] = $this->ownerAndWedding();
        $member = WeddingMember::factory()->for($wedding)->viewer()->create();

        $this->actingAs($owner)->patch(route('weddings.members.update', [$wedding, $member]), [
            'role' => 'editor',
        ])->assertRedirect();
        $this->assertSame(WeddingMemberRole::Editor, $member->fresh()->role);

        $this->actingAs($owner)->delete(route('weddings.members.destroy', [$wedding, $member]))->assertRedirect();
        $this->assertNotNull($member->fresh()->left_at);
    }

    public function test_editor_and_viewer_cannot_manage_other_members(): void
    {
        [$owner, $wedding] = $this->ownerAndWedding();
        $editor = WeddingMember::factory()->for($wedding)->create();
        $viewer = WeddingMember::factory()->for($wedding)->viewer()->create();

        foreach ([$editor->user, $viewer->user] as $actor) {
            $this->actingAs($actor)->patch(route('weddings.members.update', [$wedding, $viewer]), ['role' => 'editor'])->assertForbidden();
            $this->actingAs($actor)->delete(route('weddings.members.destroy', [$wedding, $editor]))->assertForbidden();
        }
    }

    public function test_non_owner_can_leave_and_owner_cannot_leave_or_be_removed(): void
    {
        [$owner, $wedding, $ownerMembership] = $this->ownerAndWedding();
        $editor = WeddingMember::factory()->for($wedding)->create();

        $this->actingAs($editor->user)->delete(route('weddings.members.leave', $wedding))->assertRedirect(route('dashboard'));
        $this->assertNotNull($editor->fresh()->left_at);

        $this->actingAs($owner)->delete(route('weddings.members.leave', $wedding))->assertSessionHasErrors('membership');
        $this->actingAs($owner)->delete(route('weddings.members.destroy', [$wedding, $ownerMembership]))->assertSessionHasErrors('membership');
        $this->assertNull($ownerMembership->fresh()->left_at);
    }

    /** @return array{User, Wedding, WeddingMember} */
    private function ownerAndWedding(): array
    {
        $owner = User::factory()->create();
        $wedding = Wedding::factory()->create();
        $membership = WeddingMember::factory()->for($wedding)->for($owner)->owner()->create();

        return [$owner, $wedding, $membership];
    }
}
