<?php

namespace Tests\Feature\Workspace;

use App\Models\User;
use App\Models\Wedding;
use App\Models\WeddingMember;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class WeddingPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_editor_and_viewer_receive_only_their_approved_capabilities(): void
    {
        $wedding = Wedding::factory()->create();
        $owner = User::factory()->create();
        $editor = User::factory()->create();
        $viewer = User::factory()->create();
        WeddingMember::factory()->for($wedding)->for($owner)->owner()->create();
        WeddingMember::factory()->for($wedding)->for($editor)->create();
        WeddingMember::factory()->for($wedding)->for($viewer)->viewer()->create();

        $this->assertTrue(Gate::forUser($owner)->allows('view', $wedding));
        $this->assertTrue(Gate::forUser($owner)->allows('update', $wedding));
        $this->assertTrue(Gate::forUser($owner)->allows('manageMembers', $wedding));
        $this->assertTrue(Gate::forUser($owner)->allows('editOperationalData', $wedding));

        $this->assertTrue(Gate::forUser($editor)->allows('view', $wedding));
        $this->assertFalse(Gate::forUser($editor)->allows('update', $wedding));
        $this->assertFalse(Gate::forUser($editor)->allows('manageMembers', $wedding));
        $this->assertTrue(Gate::forUser($editor)->allows('editOperationalData', $wedding));

        $this->assertTrue(Gate::forUser($viewer)->allows('view', $wedding));
        $this->assertFalse(Gate::forUser($viewer)->allows('update', $wedding));
        $this->assertFalse(Gate::forUser($viewer)->allows('manageMembers', $wedding));
        $this->assertFalse(Gate::forUser($viewer)->allows('editOperationalData', $wedding));
    }

    public function test_inactive_member_and_outsider_have_no_wedding_capabilities(): void
    {
        $wedding = Wedding::factory()->create();
        $inactive = User::factory()->create();
        $outsider = User::factory()->create();
        WeddingMember::factory()->for($wedding)->for($inactive)->inactive()->create();

        foreach ([$inactive, $outsider] as $user) {
            $this->assertFalse(Gate::forUser($user)->allows('view', $wedding));
            $this->assertFalse(Gate::forUser($user)->allows('update', $wedding));
            $this->assertFalse(Gate::forUser($user)->allows('manageMembers', $wedding));
            $this->assertFalse(Gate::forUser($user)->allows('editOperationalData', $wedding));
        }
    }
}
