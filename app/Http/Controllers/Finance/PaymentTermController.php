<?php

namespace App\Http\Controllers\Finance;

use App\Actions\Finance\RecordPaymentTermPaymentAction;
use App\Actions\Finance\ReorderPaymentTermsAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\RecordPaymentRequest;
use App\Http\Requests\Finance\ReorderPaymentTermsRequest;
use App\Http\Requests\Finance\StorePaymentTermRequest;
use App\Http\Requests\Finance\UpdatePaymentTermRequest;
use App\Models\BudgetItem;
use App\Models\PaymentTerm;
use App\Models\Wedding;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;

class PaymentTermController extends Controller
{
    public function store(StorePaymentTermRequest $request, Wedding $wedding, BudgetItem $budget_item): RedirectResponse
    {
        abort_unless($budget_item->wedding_id === $wedding->id, 404);

        $maxOrder = (int) $budget_item->paymentTerms()->max('sort_order');

        $budget_item->paymentTerms()->create([
            'wedding_id' => $wedding->id,
            ...$request->paymentTermData(),
            'sort_order' => $maxOrder + 10,
            'actual_paid_amount' => 0,
            'paid_at' => null,
            'status' => 'planned',
        ]);

        return to_route('weddings.finance.budget.show', [$wedding, $budget_item])->with('success', 'Termin pembayaran berhasil ditambahkan.');
    }

    public function update(UpdatePaymentTermRequest $request, Wedding $wedding, BudgetItem $budget_item, PaymentTerm $payment_term): RedirectResponse
    {
        $this->authorizeSameItem($wedding, $budget_item, $payment_term);

        $data = $request->paymentTermData();
        $before = ['name' => $payment_term->name, 'planned_amount' => $payment_term->planned_amount, 'due_date' => $payment_term->due_date?->toDateString()];

        $payment_term->update($data);

        // Planned changes can invalidate the stored derived state.
        $payment_term->update(['status' => $payment_term->derivedStatus()]);

        $wedding->activityLogs()->create([
            'actor_id' => $request->user()->id,
            'action' => 'payment.term.updated',
            'subject_type' => PaymentTerm::class,
            'subject_id' => $payment_term->id,
            'changes' => ['before' => $before, 'after' => ['name' => $payment_term->name, 'planned_amount' => $payment_term->planned_amount, 'due_date' => $payment_term->due_date?->toDateString()]],
        ]);

        return to_route('weddings.finance.budget.show', [$wedding, $budget_item])->with('success', 'Termin pembayaran berhasil diperbarui.');
    }

    public function recordPayment(RecordPaymentRequest $request, Wedding $wedding, BudgetItem $budget_item, PaymentTerm $payment_term, RecordPaymentTermPaymentAction $recorder): RedirectResponse
    {
        $this->authorizeSameItem($wedding, $budget_item, $payment_term);

        try {
            $recorder->execute($request->user(), $payment_term, $request->actualPaidAmount(), $request->paidAt());
        } catch (RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        $overpaid = $payment_term->actual_paid_amount > $payment_term->planned_amount;

        return to_route('weddings.finance.budget.show', [$wedding, $budget_item])->with(
            $overpaid ? 'warning' : 'success',
            $overpaid
                ? 'Pembayaran lebih besar dari jumlah rencana. Jumlah komit tidak diubah.'
                : 'Pembayaran berhasil dicatat.',
        );
    }

    public function reorder(ReorderPaymentTermsRequest $request, Wedding $wedding, BudgetItem $budget_item, ReorderPaymentTermsAction $reorderer): RedirectResponse
    {
        abort_unless($budget_item->wedding_id === $wedding->id, 404);

        try {
            $reorderer->execute($request->user(), $budget_item, $request->termUuids());
        } catch (RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return to_route('weddings.finance.budget.show', [$wedding, $budget_item])->with('success', 'Urutan termin berhasil diperbarui.');
    }

    public function destroy(Request $request, Wedding $wedding, BudgetItem $budget_item, PaymentTerm $payment_term): RedirectResponse
    {
        $this->authorizeSameItem($wedding, $budget_item, $payment_term);

        if ($payment_term->actual_paid_amount > 0) {
            return back()->with('error', 'Termin dengan pembayaran tercatat tidak dapat dihapus. Nolkan pembayarannya terlebih dahulu.');
        }

        $payment_term->delete();

        return to_route('weddings.finance.budget.show', [$wedding, $budget_item])->with('success', 'Termin pembayaran berhasil dihapus.');
    }

    private function authorizeSameItem(Wedding $wedding, BudgetItem $budget_item, PaymentTerm $payment_term): void
    {
        abort_unless($budget_item->wedding_id === $wedding->id, 404);
        abort_unless($payment_term->wedding_id === $wedding->id && $payment_term->budget_item_id === $budget_item->id, 404);
    }
}
