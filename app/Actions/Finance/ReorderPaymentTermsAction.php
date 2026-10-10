<?php

namespace App\Actions\Finance;

use App\Models\BudgetItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Transactionally reorders a budget item's payment terms to match the given
 * ordered list of term UUIDs (D-006).
 */
class ReorderPaymentTermsAction
{
    /**
     * @param  list<string>  $orderedTermUuids
     */
    public function execute(User $actor, BudgetItem $item, array $orderedTermUuids): void
    {
        $terms = $item->paymentTerms()->get(['id', 'uuid']);

        if (count($orderedTermUuids) !== $terms->count()) {
            throw new RuntimeException('Daftar urutan tidak memuat semua termin pembayaran.');
        }

        $byUuid = $terms->keyBy('uuid');

        foreach ($orderedTermUuids as $uuid) {
            if (! $byUuid->has($uuid)) {
                throw new RuntimeException('Termin pembayaran tidak ditemukan pada item ini.');
            }
        }

        DB::transaction(function () use ($item, $byUuid, $orderedTermUuids): void {
            foreach ($orderedTermUuids as $index => $uuid) {
                $item->paymentTerms()
                    ->whereKey($byUuid[$uuid]->id)
                    ->update(['sort_order' => ($index + 1) * 10]);
            }
        });
    }
}
