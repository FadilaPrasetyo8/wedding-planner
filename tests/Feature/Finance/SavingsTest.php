<?php

namespace Tests\Feature\Finance;

use App\Actions\Weddings\CreateWeddingAction;
use App\Models\SavingContribution;
use App\Models\User;
use App\Models\Wedding;
use App\Models\WeddingMember;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class SavingsTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private User $editor;

    private User $viewer;

    private User $outsider;

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

        $this->outsider = User::factory()->create();
    }

    public function test_goal_crud_and_history_blocking_delete(): void
    {
        // Editor creates a goal through the route.
        $this->actingAs($this->editor)
            ->post(route('weddings.finance.savings.store', $this->wedding), [
                'name' => 'Dana Resepsi',
                'target_amount' => 30_000_000,
                'target_date' => now()->addMonths(6)->toDateString(),
                'notes' => 'Tabungan bulanan',
            ])->assertRedirect()->assertSessionHas('success');

        $goal = $this->wedding->savingGoals()->where('name', 'Dana Resepsi')->firstOrFail();
        $this->assertTrue($goal->is_active);

        // Viewer cannot mutate.
        $this->actingAs($this->viewer)
            ->patch(route('weddings.finance.savings.update', ['wedding' => $this->wedding, 'saving_goal' => $goal]), [
                'name' => 'Perubahan Viewer',
                'target_amount' => 1,
            ])->assertForbidden();

        // Editor updates, is_active omitted must not deactivate.
        $this->actingAs($this->editor)
            ->patch(route('weddings.finance.savings.update', ['wedding' => $this->wedding, 'saving_goal' => $goal]), [
                'name' => 'Dana Resepsi',
                'target_amount' => 35_000_000,
            ])->assertRedirect();
        $this->assertSame(35_000_000, $goal->fresh()->target_amount);
        $this->assertTrue($goal->fresh()->is_active, 'Omitting is_active must not deactivate a saving goal.');

        $this->assertDatabaseHas('activity_logs', [
            'wedding_id' => $this->wedding->id,
            'actor_id' => $this->editor->id,
            'action' => 'savings.goal.updated',
        ]);

        // Without contributions, delete is allowed.
        $this->actingAs($this->owner)
            ->delete(route('weddings.finance.savings.destroy', ['wedding' => $this->wedding, 'saving_goal' => $goal]))
            ->assertRedirect()->assertSessionHas('success');
        $this->assertNull($goal->fresh());

        // A goal WITH contributions cannot be deleted, but can be deactivated.
        $goal = $this->wedding->savingGoals()->create([
            'name' => 'Dana Dekorasi',
            'target_amount' => 10_000_000,
            'target_date' => null,
            'notes' => null,
            'is_active' => true,
        ]);
        SavingContribution::query()->create([
            'wedding_id' => $this->wedding->id,
            'saving_goal_id' => $goal->id,
            'saving_contributor_id' => $this->wedding->savingContributors()->create([
                'wedding_member_id' => null,
                'label' => 'Keluarga',
                'sort_order' => 10,
                'is_active' => true,
            ])->id,
            'amount' => 500_000,
            'contributed_on' => now()->toDateString(),
            'source' => 'transfer',
            'notes' => null,
        ]);

        $this->actingAs($this->owner)
            ->delete(route('weddings.finance.savings.destroy', ['wedding' => $this->wedding, 'saving_goal' => $goal]))
            ->assertRedirect()->assertSessionHas('error');
        $this->assertNotNull($goal->fresh());

        $this->actingAs($this->owner)
            ->patch(route('weddings.finance.savings.update', ['wedding' => $this->wedding, 'saving_goal' => $goal]), [
                'name' => 'Dana Dekorasi',
                'target_amount' => 10_000_000,
                'is_active' => false,
            ])->assertRedirect();
        $this->assertFalse($goal->fresh()->is_active);
    }

    public function test_contributors_support_membership_link_or_custom_label(): void
    {
        $ownerMembership = $this->wedding->activeMemberships()->where('user_id', $this->owner->id)->firstOrFail();

        // Membership-linked contributor.
        $this->actingAs($this->editor)
            ->post(route('weddings.finance.savings.contributors.store', $this->wedding), [
                'label' => 'Ayu (Owner)',
                'membership_id' => $ownerMembership->id,
            ])->assertRedirect()->assertSessionHas('success');

        // Custom-label contributor without membership.
        $this->actingAs($this->editor)
            ->post(route('weddings.finance.savings.contributors.store', $this->wedding), [
                'label' => 'Keluarga Besar',
            ])->assertRedirect()->assertSessionHas('success');

        $linked = $this->wedding->savingContributors()->where('label', 'Ayu (Owner)')->firstOrFail();
        $this->assertSame($ownerMembership->id, $linked->wedding_member_id);
        $custom = $this->wedding->savingContributors()->where('label', 'Keluarga Besar')->firstOrFail();
        $this->assertNull($custom->wedding_member_id);

        // Foreign wedding's membership id is rejected by validation (D-021: membership must belong to this wedding).
        $otherWedding = app(CreateWeddingAction::class)->execute($this->outsider, [
            'name' => 'Wedding Lain',
            'partner_one_name' => 'Cinta',
            'partner_two_name' => 'Dewa',
            'wedding_date' => now()->addYear()->toDateString(),
            'timezone' => 'Asia/Jakarta',
            'initial_event_name' => 'Resepsi',
        ]);
        $foreignMembership = $otherWedding->activeMemberships()->firstOrFail();

        $this->actingAs($this->editor)
            ->post(route('weddings.finance.savings.contributors.store', $this->wedding), [
                'label' => 'Penabung Asing',
                'membership_id' => $foreignMembership->id,
            ])->assertInvalid('membership_id');
    }

    public function test_membership_deactivation_preserves_history(): void
    {
        $editorMembership = $this->wedding->activeMemberships()->where('user_id', $this->editor->id)->firstOrFail();

        $contributor = $this->wedding->savingContributors()->create([
            'wedding_member_id' => $editorMembership->id,
            'label' => 'Editor Penabung',
            'sort_order' => 10,
            'is_active' => true,
        ]);
        $goal = $this->wedding->savingGoals()->create([
            'name' => 'Dana Resepsi',
            'target_amount' => 10_000_000,
            'target_date' => null,
            'notes' => null,
            'is_active' => true,
        ]);

        SavingContribution::query()->create([
            'wedding_id' => $this->wedding->id,
            'saving_goal_id' => $goal->id,
            'saving_contributor_id' => $contributor->id,
            'amount' => 1_000_000,
            'contributed_on' => now()->toDateString(),
            'source' => null,
            'notes' => null,
        ]);

        // Contributor with history cannot be hard-deleted; deactivate instead.
        $this->actingAs($this->owner)
            ->delete(route('weddings.finance.savings.contributors.destroy', ['wedding' => $this->wedding, 'saving_contributor' => $contributor]))
            ->assertRedirect()->assertSessionHas('error');
        $this->assertNotNull($contributor->fresh());

        $this->actingAs($this->owner)
            ->patch(route('weddings.finance.savings.contributors.update', ['wedding' => $this->wedding, 'saving_contributor' => $contributor]), [
                'label' => 'Editor Penabung',
                'is_active' => false,
            ])->assertRedirect();
        $this->assertFalse($contributor->fresh()->is_active);

        // The contribution history survives with the contributor label intact.
        $this->assertSame(1, SavingContribution::query()->where('saving_contributor_id', $contributor->id)->count());
        $this->assertSame('Editor Penabung', $contributor->fresh()->label);
    }

    public function test_contributions_are_recorded_audited_and_scoped(): void
    {
        $goal = $this->wedding->savingGoals()->create([
            'name' => 'Dana Resepsi',
            'target_amount' => 10_000_000,
            'target_date' => null,
            'notes' => null,
            'is_active' => true,
        ]);
        $contributor = $this->wedding->savingContributors()->create([
            'wedding_member_id' => null,
            'label' => 'Keluarga',
            'sort_order' => 10,
            'is_active' => true,
        ]);

        // Editor records a contribution through the route.
        $this->actingAs($this->editor)
            ->post(route('weddings.finance.savings.contributions.store', $this->wedding), [
                'saving_goal_uuid' => $goal->uuid,
                'saving_contributor_uuid' => $contributor->uuid,
                'amount' => 2_500_000,
                'contributed_on' => now()->toDateString(),
                'source' => 'transfer',
                'notes' => 'Setoran pertama',
            ])->assertRedirect()->assertSessionHas('success');

        $contribution = SavingContribution::query()->where('saving_goal_id', $goal->id)->firstOrFail();
        $this->assertSame(2_500_000, $contribution->amount);

        $this->assertDatabaseHas('activity_logs', [
            'wedding_id' => $this->wedding->id,
            'actor_id' => $editorId = $this->editor->id,
            'action' => 'savings.contribution.created',
        ]);

        // Zero/negative amounts are rejected.
        $this->actingAs($this->editor)
            ->post(route('weddings.finance.savings.contributions.store', $this->wedding), [
                'saving_goal_uuid' => $goal->uuid,
                'saving_contributor_uuid' => $contributor->uuid,
                'amount' => 0,
                'contributed_on' => now()->toDateString(),
            ])->assertInvalid('amount');

        // Foreign goal uuid is rejected.
        $this->actingAs($this->editor)
            ->post(route('weddings.finance.savings.contributions.store', $this->wedding), [
                'saving_goal_uuid' => (string) Str::uuid(),
                'saving_contributor_uuid' => $contributor->uuid,
                'amount' => 100_000,
                'contributed_on' => now()->toDateString(),
            ])->assertInvalid('saving_goal_uuid');

        // Viewer cannot record.
        $this->actingAs($this->viewer)
            ->post(route('weddings.finance.savings.contributions.store', $this->wedding), [
                'saving_goal_uuid' => $goal->uuid,
                'saving_contributor_uuid' => $contributor->uuid,
                'amount' => 100_000,
                'contributed_on' => now()->toDateString(),
            ])->assertForbidden();

        // Editor updates the amount; audit covers before/after.
        $this->actingAs($this->editor)
            ->patch(route('weddings.finance.savings.contributions.update', ['wedding' => $this->wedding, 'saving_contribution' => $contribution]), [
                'amount' => 3_000_000,
                'contributed_on' => $contribution->contributed_on->toDateString(),
            ])->assertRedirect();
        $this->assertSame(3_000_000, $contribution->fresh()->amount);
        $this->assertDatabaseHas('activity_logs', [
            'wedding_id' => $this->wedding->id,
            'action' => 'savings.contribution.updated',
        ]);

        // Deletion is audited.
        $this->actingAs($this->owner)
            ->delete(route('weddings.finance.savings.contributions.destroy', ['wedding' => $this->wedding, 'saving_contribution' => $contribution]))
            ->assertRedirect();
        $this->assertNull($contribution->fresh());
        $this->assertDatabaseHas('activity_logs', [
            'wedding_id' => $this->wedding->id,
            'action' => 'savings.contribution.deleted',
        ]);
    }

    public function test_savings_summary_and_isolation(): void
    {
        $goal = $this->wedding->savingGoals()->create([
            'name' => 'Dana Resepsi',
            'target_amount' => 10_000_000,
            'target_date' => null,
            'notes' => null,
            'is_active' => true,
        ]);
        $otherGoal = $this->wedding->savingGoals()->create([
            'name' => 'Dana Lain',
            'target_amount' => 2_000_000,
            'target_date' => null,
            'notes' => null,
            'is_active' => false,
        ]);
        $contributor = $this->wedding->savingContributors()->create([
            'wedding_member_id' => null,
            'label' => 'Keluarga',
            'sort_order' => 10,
            'is_active' => true,
        ]);

        SavingContribution::query()->create([
            'wedding_id' => $this->wedding->id,
            'saving_goal_id' => $goal->id,
            'saving_contributor_id' => $contributor->id,
            'amount' => 2_000_000,
            'contributed_on' => now()->toDateString(),
            'source' => null,
            'notes' => null,
        ]);

        $response = $this->actingAs($this->owner)
            ->get(route('weddings.finance.savings.index', $this->wedding))
            ->assertOk();

        $page = $response->viewData('page')['props'];
        // Only the active goal's target counts towards the summary.
        $this->assertSame(10_000_000, $page['summary']['targetTotal']);
        $this->assertSame(2_000_000, $page['summary']['collectedTotal']);
        $this->assertSame(8_000_000, $page['summary']['gap']);
        $this->assertSame(20, $page['summary']['percentage']);

        $goalRow = collect($page['goals'])->first(fn ($row) => $row['uuid'] === $goal->uuid);
        $this->assertSame(2_000_000, $goalRow['collectedAmount']);
        $this->assertSame(20, $goalRow['progressPercentage']);
        $this->assertFalse(collect($page['goals'])->first(fn ($row) => $row['uuid'] === $otherGoal->uuid)['isActive']);

        // Outsider gets 403.
        $this->actingAs($this->outsider)
            ->get(route('weddings.finance.savings.index', $this->wedding))
            ->assertForbidden();
    }
}
