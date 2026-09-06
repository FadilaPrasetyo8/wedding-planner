<?php

namespace Tests\Feature;

use App\Enums\WeddingMemberRole;
use App\Models\User;
use App\Models\Wedding;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ConfigurationSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_shared_inertia_props_do_not_expose_server_credentials(): void
    {
        $user = User::factory()->create();
        $wedding = Wedding::factory()->create();
        $wedding->memberships()->create([
            'user_id' => $user->id,
            'role' => WeddingMemberRole::Owner,
            'joined_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('weddings.show', $wedding))
            ->assertInertia(fn (Assert $page) => $page
                ->has('name')
                ->has('auth.user')
                ->has('sidebarOpen')
                ->missing('database')
                ->missing('db')
                ->missing('supabase')
                ->missing('secrets')
                ->missing('environment'),
            );
    }

    public function test_test_database_is_distinct_from_development_database(): void
    {
        $this->assertSame('pgsql', config('database.default'));
        $this->assertSame('wedding_planner_test', config('database.connections.pgsql.database'));
        $this->assertNotSame('wedding_planner', config('database.connections.pgsql.database'));
    }
}
