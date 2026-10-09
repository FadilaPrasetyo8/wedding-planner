<?php

namespace App\Actions\Finance;

use App\Enums\PaymentTermStatus;
use App\Models\PaymentTerm;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Records the actual settlement on a payment term and derives its state.
 * Positive actual amounts require a paid timestamp; zero clears it (spec M3).
 */
class RecordPaymentTermPaymentAction
{
    public function execute(User $actor, PaymentTerm $term, int $actualPaidAmount, ?string $paidAt = null): PaymentTerm
    {
        if ($actualPaidAmount < 0) {
            throw new RuntimeException('Jumlah bayar aktual tidak boleh negatif.');
        }

        return DB::transaction(function () use ($actor, $term, $actualPaidAmount, $paidAt): PaymentTerm {
            $before = [
                'actual_paid_amount' => $term->actual_paid_amount,
                'paid_at' => $term->paid_at?->toIso8601String(),
                'status' => $term->status->value,
            ];

            $resolvedPaidAt = $actualPaidAmount > 0
                ? ($paidAt !== null && $paidAt !== '' ? $paidAt : now())
                : null;

            $term->update([
                'actual_paid_amount' => $actualPaidAmount,
                'paid_at' => $resolvedPaidAt,
                'status' => $this->deriveStatus($actualPaidAmount, $term->planned_amount),
            ]);

            $term->wedding->activityLogs()->create([
                'actor_id' => $actor->id,
                'action' => 'payment.term.payment_recorded',
                'subject_type' => PaymentTerm::class,
                'subject_id' => $term->id,
                'changes' => [
                    'before' => $before,
                    'after' => [
                        'actual_paid_amount' => $term->actual_paid_amount,
                        'paid_at' => $term->paid_at?->toIso8601String(),
                        'status' => $term->status->value,
                    ],
                ],
            ]);

            return $term;
        });
    }

    private function deriveStatus(int $actualPaidAmount, int $plannedAmount): PaymentTermStatus
    {
        return match (true) {
            $actualPaidAmount >= $plannedAmount && $plannedAmount > 0 => PaymentTermStatus::Paid,
            $actualPaidAmount > 0 => PaymentTermStatus::PartiallyPaid,
            default => PaymentTermStatus::Planned,
        };
    }
}
