<?php

namespace App\Support\Finance;

use App\Enums\BudgetItemStatus;
use App\Enums\PaymentTermStatus;
use App\Models\Wedding;

/**
 * Authoritative whole-rupiah finance aggregates for a wedding.
 *
 * All amounts derive from wedding-scoped source records; nothing here trusts
 * browser-submitted totals. Negative outstanding values intentionally surface
 * overpayment instead of rewriting committed amounts.
 */
class FinanceCalculations
{
    /** @return array{estimatedTotal: int, committedTotal: int, paidTotal: int, outstanding: int, draftCount: int, overpaid: bool} */
    public function budgetSummary(Wedding $wedding): array
    {
        $estimatedTotal = (int) $wedding->budgetItems()
            ->where('status', '!=', BudgetItemStatus::Cancelled)
            ->sum('estimated_amount');
        $committedTotal = (int) $wedding->budgetItems()
            ->where('status', BudgetItemStatus::Committed)
            ->sum('final_amount');
        $draftCount = (int) $wedding->budgetItems()
            ->where('status', BudgetItemStatus::Draft)
            ->count();

        $paidTotal = $this->paidTotal($wedding);
        $outstanding = $committedTotal - $paidTotal;

        return [
            'estimatedTotal' => $estimatedTotal,
            'committedTotal' => $committedTotal,
            'paidTotal' => $paidTotal,
            'outstanding' => $outstanding,
            'draftCount' => $draftCount,
            'overpaid' => $outstanding < 0,
        ];
    }

    /**
     * Paid total counts non-cancelled terms with a positive actual amount that
     * belong to committed, non-cancelled budget items (D-019).
     */
    public function paidTotal(Wedding $wedding): int
    {
        return (int) $wedding->paymentTerms()
            ->where('payment_terms.status', '!=', PaymentTermStatus::Cancelled)
            ->whereHas('budgetItem', fn ($item) => $item
                ->where('status', BudgetItemStatus::Committed)
                ->where('budget_items.status', '!=', BudgetItemStatus::Cancelled))
            ->sum('actual_paid_amount');
    }

    /** @return array<string, int> final committed amount per active category name */
    public function categoryBreakdown(Wedding $wedding): array
    {
        return $wedding->budgetCategories()
            ->orderBy('sort_order')
            ->get(['id', 'name'])
            ->mapWithKeys(fn ($category): array => [
                $category->name => (int) $wedding->budgetItems()
                    ->where('budget_category_id', $category->id)
                    ->where('status', BudgetItemStatus::Committed)
                    ->sum('final_amount'),
            ])
            ->all();
    }

    /** @return array<string, int> final committed amount per event name */
    public function eventBreakdown(Wedding $wedding): array
    {
        return $wedding->events()
            ->orderBy('event_date')
            ->get(['id', 'name'])
            ->mapWithKeys(fn ($event): array => [
                $event->name => (int) $wedding->budgetItems()
                    ->where('wedding_event_id', $event->id)
                    ->where('status', BudgetItemStatus::Committed)
                    ->sum('final_amount'),
            ])
            ->all();
    }

    /**
     * Upcoming unpaid or partially-paid terms ordered by due date.
     *
     * @return list<array<string, mixed>>
     */
    public function upcomingPayments(Wedding $wedding, int $limit = 5): array
    {
        $today = now($wedding->timezone)->startOfDay();

        $payments = $wedding->paymentTerms()
            ->with('budgetItem:id,uuid,name')
            ->whereIn('payment_terms.status', [PaymentTermStatus::Planned, PaymentTermStatus::PartiallyPaid])
            ->whereHas('budgetItem', fn ($item) => $item->where('status', BudgetItemStatus::Committed))
            ->whereNotNull('due_date')
            ->whereDate('due_date', '>=', $today->toDateString())
            ->orderBy('due_date')->orderBy('payment_terms.id')
            ->limit($limit)
            ->get()
            ->map(fn ($term): array => [
                'uuid' => $term->uuid,
                'name' => $term->name,
                'budgetItem' => $term->budgetItem->name,
                'dueDate' => $term->due_date?->toDateString(),
                'plannedAmount' => $term->planned_amount,
                'remainingAmount' => max(0, $term->planned_amount - $term->actual_paid_amount),
            ])
            ->all();

        return array_values($payments);
    }

    /**
     * Savings summary across active goals: target, collected, gap, and progress.
     *
     * @return array{targetTotal: int, collectedTotal: int, gap: int, percentage: int}
     */
    public function savingsSummary(Wedding $wedding): array
    {
        $targetTotal = (int) $wedding->savingGoals()->where('is_active', true)->sum('target_amount');
        $collectedTotal = (int) $wedding->savingContributions()
            ->whereHas('goal', fn ($goal) => $goal->where('is_active', true))
            ->sum('amount');

        return [
            'targetTotal' => $targetTotal,
            'collectedTotal' => $collectedTotal,
            'gap' => $targetTotal - $collectedTotal,
            'percentage' => $targetTotal === 0 ? 0 : (int) round(($collectedTotal / $targetTotal) * 100),
        ];
    }

    /**
     * Contributions grouped by wedding-local month, oldest first. Only
     * contributions toward active goals count, matching the collected total
     * in the savings summary.
     *
     * @return list<array{month: string, amount: int}>
     */
    public function savingsMonthlyTrend(Wedding $wedding): array
    {
        $trend = $wedding->savingContributions()
            ->whereNotNull('contributed_on')
            ->whereHas('goal', fn ($goal) => $goal->where('is_active', true))
            ->orderBy('contributed_on')
            ->get(['contributed_on', 'amount'])
            ->groupBy(fn ($contribution): string => $contribution->contributed_on
                ->copy()->setTimezone($wedding->timezone)->format('Y-m'))
            ->map(fn ($group, string $month): array => [
                'month' => $month,
                'amount' => (int) $group->sum('amount'),
            ])
            ->all();

        return array_values($trend);
    }

    /**
     * Contributions per contributor label. Only contributions toward active
     * goals count, matching the collected total in the savings summary.
     *
     * @return list<array{label: string, amount: int, percentage: int}>
     */
    public function savingsContributorBreakdown(Wedding $wedding, ?int $collectedTotal = null): array
    {
        $total = $collectedTotal ?? $this->savingsSummary($wedding)['collectedTotal'];

        $breakdown = $wedding->savingContributors()
            ->withSum(['contributions as contributed_sum' => fn ($query) => $query
                ->whereHas('goal', fn ($goal) => $goal->where('is_active', true))], 'amount')
            ->orderBy('sort_order')
            ->get(['id', 'label'])
            ->map(fn ($contributor): array => [
                'label' => $contributor->label,
                'amount' => (int) ($contributor->contributed_sum ?? 0),
                'percentage' => $total === 0 ? 0 : (int) round(((int) ($contributor->contributed_sum ?? 0) / $total) * 100),
            ])
            ->all();

        return array_values($breakdown);
    }

    /** @return int total active scenario items' amounts for one scenario */
    public function scenarioTotal(Wedding $wedding, int $scenarioId): int
    {
        return (int) $wedding->scenarioItems()
            ->where('budget_scenario_id', $scenarioId)
            ->where('is_active', true)
            ->sum('amount');
    }
}
