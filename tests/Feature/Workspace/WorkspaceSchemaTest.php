<?php

namespace Tests\Feature\Workspace;

use App\Enums\WeddingMemberRole;
use App\Models\Template;
use App\Models\User;
use App\Models\Wedding;
use App\Models\WeddingMember;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkspaceSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_wedding_uses_uuid_route_binding_and_relates_to_active_members(): void
    {
        $wedding = Wedding::factory()->create();
        $user = User::factory()->create();
        WeddingMember::factory()->for($wedding)->for($user)->create([
            'role' => WeddingMemberRole::Viewer,
        ]);

        $this->assertSame('uuid', $wedding->getRouteKeyName());
        $this->assertTrue($wedding->activeMembers->contains($user));
        $this->assertTrue($user->activeWeddings->contains($wedding));
    }

    public function test_membership_row_is_unique_even_after_it_becomes_inactive(): void
    {
        $wedding = Wedding::factory()->create();
        $user = User::factory()->create();
        WeddingMember::factory()->for($wedding)->for($user)->create(['left_at' => now()]);

        $this->expectException(QueryException::class);

        WeddingMember::factory()->for($wedding)->for($user)->create();
    }

    public function test_database_allows_only_one_active_owner_per_wedding(): void
    {
        $wedding = Wedding::factory()->create();
        WeddingMember::factory()->for($wedding)->owner()->create();

        $this->expectException(QueryException::class);

        WeddingMember::factory()->for($wedding)->owner()->create();
    }

    public function test_seeded_templates_contain_only_supported_planning_items(): void
    {
        $this->seed();

        $this->assertGreaterThanOrEqual(2, Template::query()->count());
        $this->assertSame(
            ['event', 'task'],
            Template::query()->with('sections.items')->get()
                ->flatMap->sections
                ->flatMap->items
                ->pluck('type')
                ->unique()
                ->sort()
                ->values()
                ->all(),
        );
    }
}
