<?php

namespace Tests\Feature\Finance;

use App\Enums\PaymentTermStatus;
use App\Models\BudgetItem;
use App\Models\PaymentTerm;
use App\Models\SavingContribution;
use App\Models\SavingContributor;
use App\Models\SavingGoal;
use App\Models\Wedding;
use App\Support\Finance\FinanceCalculations;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinanceCalculationsTest extends TestCase
{
    use RefreshDatabase;

    private FinanceCalculations $calculations;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculations = app(FinanceCalculations::class);
    }

    public function test_budget_summary_derives_totals_from_valid_records(): void
    {
        $wedding = Wedding::factory()->create();

        $draft = BudgetItem::factory()->for($wedding)->create(['estimated_amount' => 2_000_000]);
        $committed = BudgetItem::factory()->for($wedding)->committed()->create([
            'estimated_amount' => 10_000_000,
            'final_amount' => 9_000_000,
        ]);
        BudgetItem::factory()->for($wedding)->cancelled()->create([
            'estimated_amount' => 5_000_000,
            'final_amount' => 4_000_000,
        ]);

        PaymentTerm::factory()->for($wedding)->for($committed)->paid()->create([
            'planned_amount' => 5_000_000,
            'actual_paid_amount' => 5_000_000,
        ]);
        PaymentTerm::factory()->for($wedding)->for($committed)->partiallyPaid()->create([
            'planned_amount' => 4_000_000,
            'actual_paid_amount' => 1_000_000,
        ]);
        // Cancelled term and term on draft item must not count toward paid totals.
        PaymentTerm::factory()->for($wedding)->for($committed)->paid()->cancelled()->create([
            'planned_amount' => 3_000_000,
            'actual_paid_amount' => 3_000_000,
            'status' => PaymentTermStatus::Cancelled,
        ]);
        PaymentTerm::factory()->for($wedding)->for($draft)->paid()->create([
            'planned_amount' => 2_000_000,
            'actual_paid_amount' => 2_000_000,
        ]);

        $summary = $this->calculations->budgetSummary($wedding);

        $this->assertSame(12_000_000, $summary['estimatedTotal']);
        $this->assertSame(9_000_000, $summary['committedTotal']);
        $this->assertSame(6_000_000, $summary['paidTotal']);
        $this->assertSame(3_000_000, $summary['outstanding']);
        $this->assertSame(1, $summary['draftCount']);
        $this->assertFalse($summary['overpaid']);
    }

    public function test_overpayment_retains_negative_outstanding_without_rewriting_final_amount(): void
    {
        $wedding = Wedding::factory()->create();
        $item = BudgetItem::factory()->for($wedding)->committed()->create(['final_amount' => 5_000_000]);

        PaymentTerm::factory()->for($wedding)->for($item)->paid()->create([
            'planned_amount' => 5_000_000,
            'actual_paid_amount' => 7_000_000,
        ]);

        $summary = $this->calculations->budgetSummary($wedding);

        $this->assertSame(7_000_000, $summary['paidTotal']);
        $this->assertSame(-2_000_000, $summary['outstanding']);
        $this->assertTrue($summary['overpaid']);
        $this->assertSame(5_000_000, $item->fresh()->final_amount);
    }

    public function test_savings_summary_and_contributor_breakdown(): void
    {
        $wedding = Wedding::factory()->create();
        $goal = SavingGoal::factory()->for($wedding)->create(['target_amount' => 10_000_000]);
        $inactiveGoal = SavingGoal::factory()->for($wedding)->inactive()->create(['target_amount' => 50_000_000]);
        $contributorA = SavingContributor::factory()->for($wedding)->create(['label' => 'Keluarga A']);
        $contributorB = SavingContributor::factory()->for($wedding)->create(['label' => 'Keluarga B']);

        SavingContribution::factory()->for($wedding)->for($goal, 'goal')->for($contributorA, 'contributor')->create(['amount' => 3_000_000, 'contributed_on' => '2026-07-10']);
        SavingContribution::factory()->for($wedding)->for($goal, 'goal')->for($contributorB, 'contributor')->create(['amount' => 1_000_000, 'contributed_on' => '2026-08-15']);
        SavingContribution::factory()->for($wedding)->for($goal, 'goal')->for($contributorB, 'contributor')->create(['amount' => 1_000_000, 'contributed_on' => '2026-08-20']);
        // Contribution on an inactive goal must not count toward collected totals.
        SavingContribution::factory()->for($wedding)->for($inactiveGoal, 'goal')->for($contributorA, 'contributor')->create(['amount' => 5_000_000]);

        $summary = $this->calculations->savingsSummary($wedding);

        $this->assertSame(10_000_000, $summary['targetTotal']);
        $this->assertSame(5_000_000, $summary['collectedTotal']);
        $this->assertSame(5_000_000, $summary['gap']);
        $this->assertSame(50, $summary['percentage']);

        $breakdown = $this->calculations->savingsContributorBreakdown($wedding, $summary['collectedTotal']);
        $this->assertSame('Keluarga A', $breakdown[0]['label']);
        $this->assertSame(3_000_000, $breakdown[0]['amount']);
        $this->assertSame(60, $breakdown[0]['percentage']);
        $this->assertSame(2_000_000, $breakdown[1]['amount']);
        $this->assertSame(40, $breakdown[1]['percentage']);

        $trend = $this->calculations->savingsMonthlyTrend($wedding);
        $this->assertSame([['month' => '2026-07', 'amount' => 3_000_000], ['month' => '2026-08', 'amount' => 2_000_000]], $trend);
    }

    public function test_calculations_are_wedding_isolated(): void
    {
        $first = Wedding::factory()->create();
        $second = Wedding::factory()->create();

        $item = BudgetItem::factory()->for($first)->committed()->create(['final_amount' => 8_000_000]);
        PaymentTerm::factory()->for($first)->for($item)->paid()->create([
            'planned_amount' => 8_000_000,
            'actual_paid_amount' => 8_000_000,
        ]);

        $secondSummary = $this->calculations->budgetSummary($second);

        $this->assertSame(0, $secondSummary['estimatedTotal']);
        $this->assertSame(0, $secondSummary['paidTotal']);
        $this->assertSame(0, $secondSummary['outstanding']);
    }

    public function test_upcoming_payments_list_only_committed_unpaid_terms(): void
    {
        $wedding = Wedding::factory()->create();
        $item = BudgetItem::factory()->for($wedding)->committed()->create();
        $draft = BudgetItem::factory()->for($wedding)->create();

        PaymentTerm::factory()->for($wedding)->for($item)->create([
            'name' => 'Termin Dekat',
            'planned_amount' => 1_000_000,
            'due_date' => now()->addDays(3)->toDateString(),
        ]);
        PaymentTerm::factory()->for($wedding)->for($item)->paid()->create([
            'name' => 'Sudah Lunas',
            'planned_amount' => 1_000_000,
            'due_date' => now()->addDays(2)->toDateString(),
        ]);
        PaymentTerm::factory()->for($wedding)->for($draft)->create([
            'name' => 'Di Item Draft',
            'planned_amount' => 1_000_000,
            'due_date' => now()->addDays(1)->toDateString(),
        ]);

        $upcoming = $this->calculations->upcomingPayments($wedding);

        $this->assertCount(1, $upcoming);
        $this->assertSame('Termin Dekat', $upcoming[0]['name']);
        $this->assertSame(1_000_000, $upcoming[0]['remainingAmount']);
    }
}
