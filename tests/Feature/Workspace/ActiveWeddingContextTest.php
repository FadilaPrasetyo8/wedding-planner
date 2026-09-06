<?php

namespace Tests\Feature\Workspace;

use App\Enums\WeddingMemberRole;
use App\Models\User;
use App\Models\Wedding;
use App\Models\WeddingMember;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ActiveWeddingContextTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_uses_accessible_session_preference_then_first_wedding_then_onboarding(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('dashboard'))
            ->assertRedirect(route('weddings.create'));

        $first = Wedding::factory()->create();
        $second = Wedding::factory()->create();
        WeddingMember::factory()->for($first)->for($user)->owner()->create();
        WeddingMember::factory()->for($second)->for($user)->owner()->create();

        $this->actingAs($user)->get(route('dashboard'))
            ->assertRedirect(route('weddings.show', $first));

        $this->withSession(['active_wedding_uuid' => $second->uuid])
            ->actingAs($user)
            ->get(route('dashboard'))
            ->assertRedirect(route('weddings.show', $second));
    }

    public function test_canonical_wedding_route_requires_active_membership_and_sets_context(): void
    {
        $member = User::factory()->create();
        $outsider = User::factory()->create();
        $wedding = Wedding::factory()->create();
        WeddingMember::factory()->for($wedding)->for($member)->viewer()->create();

        $this->actingAs($outsider)->get(route('weddings.show', $wedding))->assertForbidden();

        $this->actingAs($member)->get(route('weddings.show', $wedding))
            ->assertOk()
            ->assertSessionHas('active_wedding_uuid', $wedding->uuid)
            ->assertInertia(fn (Assert $page) => $page
                ->component('weddings/show')
                ->where('activeWedding.uuid', $wedding->uuid)
                ->where('activeMembership.role', WeddingMemberRole::Viewer->value)
                ->where('capabilities.manageMembers', false)
                ->where('capabilities.editWedding', false));
    }

    public function test_inactive_members_and_soft_deleted_weddings_are_unavailable(): void
    {
        $user = User::factory()->create();
        $inactiveWedding = Wedding::factory()->create();
        WeddingMember::factory()->for($inactiveWedding)->for($user)->inactive()->create();

        $this->actingAs($user)->get(route('weddings.show', $inactiveWedding))->assertForbidden();

        $deletedWedding = Wedding::factory()->create();
        WeddingMember::factory()->for($deletedWedding)->for($user)->owner()->create();
        $deletedWedding->delete();

        $this->actingAs($user)->get('/weddings/'.$deletedWedding->uuid)->assertNotFound();
    }

    public function test_shared_wedding_list_contains_only_users_active_memberships(): void
    {
        $user = User::factory()->create();
        $accessible = Wedding::factory()->create();
        $inactive = Wedding::factory()->create();
        $other = Wedding::factory()->create();
        WeddingMember::factory()->for($accessible)->for($user)->owner()->create();
        WeddingMember::factory()->for($inactive)->for($user)->viewer()->inactive()->create();
        WeddingMember::factory()->for($other)->owner()->create();

        $this->actingAs($user)->get(route('weddings.show', $accessible))
            ->assertInertia(fn (Assert $page) => $page
                ->has('weddings', 1)
                ->where('weddings.0.uuid', $accessible->uuid));
    }
}
