<?php

namespace App\Actions\Finance;

use App\Enums\BudgetItemStatus;
use App\Models\BudgetItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Commits a budget item; committing requires a non-null final amount (D-019).
 */
class CommitBudgetItemAction
{
    public function execute(User $actor, BudgetItem $item, int $finalAmount): BudgetItem
    {
        if ($item->status === BudgetItemStatus::Cancelled) {
            throw new RuntimeException('Item anggaran yang dibatalkan tidak dapat dikomit.');
        }

        if ($finalAmount < 0) {
            throw new RuntimeException('Jumlah final tidak boleh negatif.');
        }

        return DB::transaction(function () use ($actor, $item, $finalAmount): BudgetItem {
            $before = ['status' => $item->status->value, 'final_amount' => $item->final_amount];
            $item->update(['status' => BudgetItemStatus::Committed, 'final_amount' => $finalAmount]);

            $item->wedding->activityLogs()->create([
                'actor_id' => $actor->id,
                'action' => 'budget.item.committed',
                'subject_type' => BudgetItem::class,
                'subject_id' => $item->id,
                'changes' => ['before' => $before, 'after' => ['status' => $item->status->value, 'final_amount' => $item->final_amount]],
            ]);

            return $item;
        });
    }
}
