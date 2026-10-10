<?php

namespace App\Actions\Finance;

use App\Enums\BudgetItemStatus;
use App\Models\BudgetItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Cancels a budget item. Committed items or items with payment history are
 * cancelled rather than deleted (D-019); cancelled rows stay historical.
 */
class CancelBudgetItemAction
{
    public function execute(User $actor, BudgetItem $item): BudgetItem
    {
        return DB::transaction(function () use ($actor, $item): BudgetItem {
            $before = ['status' => $item->status->value];
            $item->update(['status' => BudgetItemStatus::Cancelled]);

            $item->wedding->activityLogs()->create([
                'actor_id' => $actor->id,
                'action' => 'budget.item.cancelled',
                'subject_type' => BudgetItem::class,
                'subject_id' => $item->id,
                'changes' => ['before' => $before, 'after' => ['status' => $item->status->value]],
            ]);

            return $item;
        });
    }
}
