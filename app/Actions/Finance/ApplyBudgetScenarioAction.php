<?php

namespace App\Actions\Finance;

use App\Enums\BudgetItemStatus;
use App\Models\BudgetScenario;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Applies a scenario by transactionally appending independent draft budget
 * items (D-020). Never merges or deletes existing budget data. Provenance uses
 * immutable name snapshots and a batch UUID, not source-item linkage.
 */
class ApplyBudgetScenarioAction
{
    public function execute(User $actor, BudgetScenario $scenario, bool $confirmed = false): string
    {
        return DB::transaction(function () use ($actor, $scenario, $confirmed): string {
            $alreadyApplied = $scenario->wedding->budgetItems()
                ->where('source_scenario_name', $scenario->name)
                ->exists();

            if ($alreadyApplied && ! $confirmed) {
                throw new RuntimeException('Skenario ini sudah pernah diterapkan. Konfirmasi ulang untuk menerapkan lagi.');
            }

            $items = $scenario->scenarioItems()->where('is_active', true)->orderBy('sort_order')->get();

            if ($items->isEmpty()) {
                throw new RuntimeException('Skenario tidak memiliki alokasi aktif untuk diterapkan.');
            }

            $batchId = (string) Str::uuid();

            foreach ($items as $item) {
                $scenario->wedding->budgetItems()->create([
                    'budget_category_id' => $item->budget_category_id,
                    'wedding_event_id' => $item->wedding_event_id,
                    'name' => $item->name,
                    'estimated_amount' => $item->amount,
                    'final_amount' => null,
                    'status' => BudgetItemStatus::Draft,
                    'source_scenario_name' => $scenario->name,
                    'source_item_name' => $item->name,
                    'application_batch_id' => $batchId,
                ]);
            }

            $scenario->wedding->activityLogs()->create([
                'actor_id' => $actor->id,
                'action' => 'budget.scenario.applied',
                'subject_type' => BudgetScenario::class,
                'subject_id' => $scenario->id,
                'changes' => ['scenario' => $scenario->name, 'items' => $items->count(), 'batch' => $batchId],
            ]);

            return $batchId;
        });
    }

    public function hasBeenApplied(BudgetScenario $scenario): bool
    {
        return $scenario->wedding->budgetItems()
            ->where('source_scenario_name', $scenario->name)
            ->exists();
    }
}
