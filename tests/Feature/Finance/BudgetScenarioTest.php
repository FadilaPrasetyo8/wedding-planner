<?php

namespace Tests\Feature\Finance;

use App\Actions\Finance\ApplyBudgetScenarioAction;
use App\Actions\Weddings\CreateWeddingAction;
use App\Models\User;
use App\Models\Wedding;
use App\Models\WeddingMember;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BudgetScenarioTest extends TestCase
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

    public function test_owner_and_editor_can_create_and_edit_scenarios_but_viewer_cannot(): void
    {
        $index = 'weddings.finance.scenarios.index';

        $this->actingAs($this->editor)->post(route('weddings.finance.scenarios.store', $this->wedding), [
            'name' => 'Skenario Kustom',
        ])->assertRedirect();

        $this->assertSame(7, $this->wedding->budgetScenarios()->count());

        $this->actingAs($this->viewer)->post(route('weddings.finance.scenarios.store', $this->wedding), [
            'name' => 'Skenario Viewer',
        ])->assertForbidden();

        $this->actingAs($this->owner)->get(route($index, $this->wedding))->assertOk();
        $this->actingAs($this->viewer)->get(route($index, $this->wedding))->assertOk();
    }

    public function test_non_member_receives_403_and_cross_wedding_nested_resource_404(): void
    {
        $this->actingAs($this->outsider)
            ->get(route('weddings.finance.scenarios.index', $this->wedding))
            ->assertForbidden();

        $otherWedding = app(CreateWeddingAction::class)->execute($this->outsider, [
            'name' => 'Wedding Lain',
            'partner_one_name' => 'Cinta',
            'partner_two_name' => 'Dewa',
            'wedding_date' => now()->addYear()->toDateString(),
            'timezone' => 'Asia/Jakarta',
            'initial_event_name' => 'Resepsi',
        ]);

        $foreignScenario = $otherWedding->budgetScenarios()->first();

        // Owner of the first wedding cannot see the other wedding's nested resource.
        $this->actingAs($this->owner)
            ->get(route('weddings.finance.scenarios.show', [$this->wedding, $foreignScenario]))
            ->assertNotFound();
    }

    public function test_applying_scenario_appends_independent_draft_items_transactionally(): void
    {
        $scenario = $this->wedding->budgetScenarios()->where('name', 'Hemat Rp10 Juta')->firstOrFail();
        $activeItems = $scenario->scenarioItems()->where('is_active', true)->count();
        $existingBudgetCount = $this->wedding->budgetItems()->count();

        $response = $this->actingAs($this->owner)
            ->post(route('weddings.finance.scenarios.apply', [$this->wedding, $scenario]));

        $response->assertRedirect(route('weddings.finance.budget.index', $this->wedding));

        $this->assertSame($existingBudgetCount + $activeItems, $this->wedding->budgetItems()->count());

        $applied = $this->wedding->budgetItems()->where('source_scenario_name', $scenario->name)->get();
        $this->assertCount($activeItems, $applied);
        $this->assertTrue($applied->every(fn ($item) => $item->status->value === 'draft'));
        $this->assertTrue($applied->every(fn ($item) => $item->final_amount === null));
        $this->assertSame(1, $applied->pluck('application_batch_id')->unique()->count());
        $this->assertTrue($applied->every(fn ($item) => $item->source_item_name !== null));

        $this->assertDatabaseHas('activity_logs', [
            'wedding_id' => $this->wedding->id,
            'actor_id' => $this->owner->id,
            'action' => 'budget.scenario.applied',
        ]);
    }

    public function test_repeated_application_requires_confirmation(): void
    {
        $scenario = $this->wedding->budgetScenarios()->firstOrFail();
        $activeItems = $scenario->scenarioItems()->where('is_active', true)->count();

        $this->actingAs($this->owner)->post(route('weddings.finance.scenarios.apply', [$this->wedding, $scenario]));
        $afterFirst = $this->wedding->budgetItems()->count();

        // Without confirmation, repeat application fails and changes nothing.
        $this->actingAs($this->owner)
            ->post(route('weddings.finance.scenarios.apply', [$this->wedding, $scenario]))
            ->assertRedirect()
            ->assertSessionHas('error');
        $this->assertSame($afterFirst, $this->wedding->budgetItems()->count());

        // With confirmation, it appends another independent batch.
        $this->actingAs($this->owner)
            ->post(route('weddings.finance.scenarios.apply', [$this->wedding, $scenario]), ['confirm' => true])
            ->assertRedirect();
        $this->assertSame($afterFirst + $activeItems, $this->wedding->budgetItems()->count());
    }

    public function test_application_rolls_back_atomically_on_failure(): void
    {
        $scenario = $this->wedding->budgetScenarios()->firstOrFail();
        $countBefore = $this->wedding->budgetItems()->count();

        // Drop the check constraint first so the row itself can be corrupted;
        // the failure then lands inside the action's transactional insert.
        DB::statement('ALTER TABLE scenario_items DROP CONSTRAINT scenario_items_amount_check');
        DB::table('scenario_items')->where('budget_scenario_id', $scenario->id)->limit(1)
            ->update(['is_active' => true, 'amount' => -5]);

        try {
            app(ApplyBudgetScenarioAction::class)->execute($this->owner, $scenario);
            $this->fail('Expected exception was not thrown.');
        } catch (QueryException) {
            // Expected: the negative amount violates the amount check constraint.
        }

        $this->assertSame($countBefore, $this->wedding->budgetItems()->count());
        $this->assertSame(0, $this->wedding->budgetItems()->where('source_scenario_name', $scenario->name)->count());
    }

    public function test_copying_scenario_creates_independent_child_rows(): void
    {
        $scenario = $this->wedding->budgetScenarios()->where('name', 'Ideal Rp30 Juta')->firstOrFail();
        $sourceItems = $scenario->scenarioItems()->orderBy('sort_order')->get();

        $response = $this->actingAs($this->editor)
            ->post(route('weddings.finance.scenarios.copy', [$this->wedding, $scenario]), [
                'name' => 'Salinan Ideal',
            ]);

        $copy = $this->wedding->budgetScenarios()->where('name', 'Salinan Ideal')->firstOrFail();
        $response->assertRedirect(route('weddings.finance.scenarios.show', [$this->wedding, $copy]));

        $this->assertFalse($copy->is_active);
        $copiedItems = $copy->scenarioItems()->orderBy('sort_order')->get();
        $this->assertCount($sourceItems->count(), $copiedItems);

        foreach ($sourceItems as $index => $source) {
            $this->assertNotSame($source->id, $copiedItems[$index]->id);
            $this->assertSame($source->amount, $copiedItems[$index]->amount);
            $this->assertSame($source->name, $copiedItems[$index]->name);
        }

        // Editing the copy does not touch the source.
        $copiedItems->first()->update(['amount' => 123]);
        $this->assertSame($sourceItems->first()->amount, $sourceItems->first()->fresh()->amount);
    }

    public function test_scenario_item_crud_is_wedding_scoped(): void
    {
        $scenario = $this->wedding->budgetScenarios()->firstOrFail();
        $category = $this->wedding->budgetCategories()->first();

        $this->actingAs($this->editor)
            ->post(route('weddings.finance.scenario-items.store', ['wedding' => $this->wedding, 'scenario' => $scenario]), [
                'name' => 'Alokasi Baru',
                'amount' => 1_500_000,
                'budget_category_uuid' => $category->uuid,
            ])->assertRedirect();

        $item = $scenario->scenarioItems()->where('name', 'Alokasi Baru')->firstOrFail();
        $this->assertSame(1_500_000, $item->amount);

        $this->actingAs($this->viewer)
            ->patch(route('weddings.finance.scenario-items.update', ['wedding' => $this->wedding, 'scenario' => $scenario, 'scenario_item' => $item]), [
                'name' => 'Perubahan Viewer',
                'amount' => 100,
            ])->assertForbidden();

        $this->actingAs($this->editor)
            ->patch(route('weddings.finance.scenario-items.update', ['wedding' => $this->wedding, 'scenario' => $scenario, 'scenario_item' => $item]), [
                'name' => 'Alokasi Baru',
                'amount' => 2_000_000,
                'budget_category_uuid' => $category->uuid,
            ])->assertRedirect();
        $this->assertSame(2_000_000, $item->fresh()->amount);
        $this->assertTrue($item->fresh()->is_active, 'Omitting is_active must not deactivate a scenario item.');

        $this->actingAs($this->editor)
            ->delete(route('weddings.finance.scenario-items.destroy', ['wedding' => $this->wedding, 'scenario' => $scenario, 'scenario_item' => $item]))
            ->assertRedirect();
        $this->assertNull($scenario->scenarioItems()->find($item->id));
    }
}
