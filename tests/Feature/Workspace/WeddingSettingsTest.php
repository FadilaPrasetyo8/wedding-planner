<?php

namespace Tests\Feature\Workspace;

use App\Models\User;
use App\Models\Wedding;
use App\Models\WeddingMember;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WeddingSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_updates_validated_wedding_settings(): void
    {
        [$owner, $wedding] = $this->ownerAndWedding();

        $this->actingAs($owner)->patch(route('weddings.settings.update', $wedding), [
            'name' => 'Pernikahan Alya dan Bima',
            'partner_one_name' => 'Alya',
            'partner_two_name' => 'Bima',
            'wedding_date' => '2027-07-20',
            'planning_start_date' => '2026-09-04',
            'timezone' => 'Asia/Makassar',
            'target_budget' => 75000000,
        ])->assertRedirect();

        $this->assertDatabaseHas('weddings', [
            'id' => $wedding->id,
            'name' => 'Pernikahan Alya dan Bima',
            'timezone' => 'Asia/Makassar',
            'target_budget' => 75000000,
        ]);
    }

    public function test_editor_and_viewer_cannot_update_or_delete_wedding(): void
    {
        [$owner, $wedding] = $this->ownerAndWedding();
        $editor = WeddingMember::factory()->for($wedding)->create();
        $viewer = WeddingMember::factory()->for($wedding)->viewer()->create();

        foreach ([$editor->user, $viewer->user] as $actor) {
            $this->actingAs($actor)->patch(route('weddings.settings.update', $wedding), $this->validSettings())->assertForbidden();
            $this->actingAs($actor)->delete(route('weddings.settings.destroy', $wedding), ['confirmation' => $wedding->name])->assertForbidden();
        }
    }

    public function test_settings_validate_dates_timezone_and_whole_rupiah_budget(): void
    {
        [$owner, $wedding] = $this->ownerAndWedding();

        $this->actingAs($owner)->patch(route('weddings.settings.update', $wedding), [
            ...$this->validSettings(),
            'planning_start_date' => '2028-01-01',
            'timezone' => 'Invalid/Zone',
            'target_budget' => -1,
        ])->assertSessionHasErrors(['planning_start_date', 'timezone', 'target_budget']);
    }

    public function test_owner_soft_deletes_only_after_exact_name_confirmation_and_preference_is_cleared(): void
    {
        [$owner, $wedding] = $this->ownerAndWedding();

        $this->withSession(['active_wedding_uuid' => $wedding->uuid])
            ->actingAs($owner)
            ->delete(route('weddings.settings.destroy', $wedding), ['confirmation' => 'salah'])
            ->assertSessionHasErrors('confirmation');
        $this->assertNull($wedding->fresh()->deleted_at);

        $this->withSession(['active_wedding_uuid' => $wedding->uuid])
            ->actingAs($owner)
            ->delete(route('weddings.settings.destroy', $wedding), ['confirmation' => $wedding->name])
            ->assertRedirect(route('dashboard'))
            ->assertSessionMissing('active_wedding_uuid');

        $this->assertSoftDeleted($wedding);
        $this->actingAs($owner)->get('/weddings/'.$wedding->uuid)->assertNotFound();
    }

    /** @return array{User, Wedding} */
    private function ownerAndWedding(): array
    {
        $owner = User::factory()->create();
        $wedding = Wedding::factory()->create();
        WeddingMember::factory()->for($wedding)->for($owner)->owner()->create();

        return [$owner, $wedding];
    }

    /** @return array<string, mixed> */
    private function validSettings(): array
    {
        return [
            'name' => 'Pernikahan',
            'partner_one_name' => 'Alya',
            'partner_two_name' => 'Bima',
            'wedding_date' => '2027-06-20',
            'planning_start_date' => null,
            'timezone' => 'Asia/Jakarta',
            'target_budget' => null,
        ];
    }
}
