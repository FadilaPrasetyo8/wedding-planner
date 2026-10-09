<?php

namespace App\Actions\Finance;

use App\Models\BudgetScenario;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Copies a scenario with entirely new independent child rows (D-020).
 * The copy starts inactive and keeps no linkage to source items.
 */
class CopyBudgetScenarioAction
{
    public function execute(User $actor, BudgetScenario $scenario, string $newName): BudgetScenario
    {
        return DB::transaction(function () use ($actor, $scenario, $newName): BudgetScenario {
            $copy = $scenario->wedding->budgetScenarios()->create([
                'name' => $newName,
                'description' => $scenario->description,
                'sort_order' => $scenario->sort_order,
                'is_active' => false,
            ]);

            $scenario->scenarioItems()->orderBy('sort_order')->get()->each(function ($item) use ($copy): void {
                $copy->scenarioItems()->create([
                    'wedding_id' => $copy->wedding_id,
                    'budget_category_id' => $item->budget_category_id,
                    'wedding_event_id' => $item->wedding_event_id,
                    'name' => $item->name,
                    'amount' => $item->amount,
                    'notes' => $item->notes,
                    'sort_order' => $item->sort_order,
                    'is_active' => $item->is_active,
                ]);
            });

            $scenario->wedding->activityLogs()->create([
                'actor_id' => $actor->id,
                'action' => 'budget.scenario.copied',
                'subject_type' => BudgetScenario::class,
                'subject_id' => $copy->id,
                'changes' => ['from' => $scenario->name, 'to' => $newName],
            ]);

            return $copy;
        });
    }
}
