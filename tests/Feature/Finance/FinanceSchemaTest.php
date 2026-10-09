<?php

namespace Tests\Feature\Finance;

use App\Enums\BudgetItemStatus;
use App\Enums\PaymentTermStatus;
use App\Models\BudgetCategory;
use App\Models\BudgetItem;
use App\Models\BudgetScenario;
use App\Models\PaymentTerm;
use App\Models\SavingContribution;
use App\Models\SavingContributor;
use App\Models\SavingGoal;
use App\Models\ScenarioItem;
use App\Models\Wedding;
use App\Models\WeddingEvent;
use App\Models\WeddingMember;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinanceSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_finance_models_expose_wedding_scoped_relationships_and_integer_money(): void
    {
        $wedding = Wedding::factory()->create();
        $category = BudgetCategory::factory()->for($wedding)->create();
        $event = WeddingEvent::factory()->for($wedding)->create();
        $scenario = BudgetScenario::factory()->for($wedding)->create();
        $scenarioItem = ScenarioItem::factory()->for($wedding)->for($scenario)->create([
            'budget_category_id' => $category->id,
            'wedding_event_id' => $event->id,
            'amount' => 15_000_000,
        ]);
        $budgetItem = BudgetItem::factory()->for($wedding)->create([
            'budget_category_id' => $category->id,
            'wedding_event_id' => $event->id,
            'estimated_amount' => 12_500_000,
            'final_amount' => 12_000_000,
            'status' => BudgetItemStatus::Committed,
        ]);
        $term = PaymentTerm::factory()->for($wedding)->for($budgetItem)->create([
            'planned_amount' => 6_000_000,
            'actual_paid_amount' => 5_500_000,
            'status' => PaymentTermStatus::PartiallyPaid,
            'paid_at' => now(),
        ]);
        $member = WeddingMember::factory()->for($wedding)->create();
        $goal = SavingGoal::factory()->for($wedding)->create(['target_amount' => 20_000_000]);
        $contributor = SavingContributor::factory()->for($wedding)->create([
            'wedding_member_id' => $member->id,
            'label' => 'Pasangan satu',
        ]);
        $contribution = SavingContribution::factory()->for($wedding)->for($goal, 'goal')->for($contributor, 'contributor')->create([
            'amount' => 1_500_000,
        ]);

        $this->assertTrue($scenario->wedding->is($wedding));
        $this->assertTrue($scenarioItem->scenario->is($scenario));
        $this->assertTrue($scenarioItem->category->is($category));
        $this->assertTrue($scenarioItem->event->is($event));
        $this->assertTrue($budgetItem->category->is($category));
        $this->assertTrue($budgetItem->event->is($event));
        $this->assertTrue($term->budgetItem->is($budgetItem));
        $this->assertTrue($contributor->membership->is($member));
        $this->assertTrue($contribution->goal->is($goal));
        $this->assertTrue($contribution->contributor->is($contributor));
        $this->assertSame(15_000_000, $scenarioItem->amount);
        $this->assertSame(12_000_000, $budgetItem->final_amount);
        $this->assertSame(5_500_000, $term->actual_paid_amount);
        $this->assertSame(1_500_000, $contribution->amount);

        $this->assertTrue($wedding->budgetCategories->first()->is($category));
        $this->assertTrue($wedding->budgetScenarios->first()->is($scenario));
        $this->assertTrue($wedding->budgetItems->first()->is($budgetItem));
        $this->assertTrue($wedding->paymentTerms->first()->is($term));
        $this->assertTrue($wedding->savingGoals->first()->is($goal));
        $this->assertTrue($wedding->savingContributors->first()->is($contributor));
        $this->assertTrue($wedding->savingContributions->first()->is($contribution));

        $this->assertSame(PaymentTermStatus::PartiallyPaid, $term->derivedStatus());
        $this->assertSame($budgetItem->uuid, $budgetItem->getRouteKey());
    }

    public function test_database_rejects_cross_wedding_finance_relationships(): void
    {
        $first = Wedding::factory()->create();
        $second = Wedding::factory()->create();
        $foreignCategory = BudgetCategory::factory()->for($second)->create();

        $this->expectException(QueryException::class);
        BudgetItem::factory()->for($first)->create([
            'budget_category_id' => $foreignCategory->id,
        ]);
    }

    public function test_one_budget_item_supports_at_least_ten_payment_terms(): void
    {
        $wedding = Wedding::factory()->create();
        $item = BudgetItem::factory()->for($wedding)->create();

        foreach (range(1, 10) as $order) {
            PaymentTerm::factory()->for($wedding)->for($item)->create([
                'name' => 'Termin '.$order,
                'sort_order' => $order * 10,
            ]);
        }

        $this->assertCount(10, $item->paymentTerms);
        $this->assertSame(
            [10, 20, 30, 40, 50, 60, 70, 80, 90, 100],
            $item->paymentTerms->pluck('sort_order')->all(),
        );
    }

    public function test_negative_money_and_invalid_states_are_rejected(): void
    {
        $wedding = Wedding::factory()->create();
        $item = BudgetItem::factory()->for($wedding)->create();

        $this->expectException(QueryException::class);
        PaymentTerm::factory()->for($wedding)->for($item)->create([
            'planned_amount' => -1_000,
        ]);
    }

    public function test_paid_state_requires_paid_at_timestamp(): void
    {
        $wedding = Wedding::factory()->create();
        $item = BudgetItem::factory()->for($wedding)->create();

        $this->expectException(QueryException::class);
        PaymentTerm::factory()->for($wedding)->for($item)->create([
            'actual_paid_amount' => 1_000_000,
            'paid_at' => null,
        ]);
    }
}
