<?php

namespace App\Http\Controllers\Finance;

use App\Actions\Finance\CancelBudgetItemAction;
use App\Actions\Finance\CommitBudgetItemAction;
use App\Enums\BudgetItemStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\CommitBudgetItemRequest;
use App\Http\Requests\Finance\StoreBudgetItemRequest;
use App\Http\Requests\Finance\UpdateBudgetItemRequest;
use App\Models\BudgetItem;
use App\Models\Wedding;
use App\Support\Finance\FinanceCalculations;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

class BudgetItemController extends Controller
{
    public function index(Request $request, Wedding $wedding, FinanceCalculations $calculations): Response
    {
        $search = trim((string) $request->string('search'));
        $statusFilter = $request->string('status')->toString();
        $categoryFilter = (string) $request->string('category');

        $items = $wedding->budgetItems()
            ->with(['category:id,uuid,name,color', 'event:id,uuid,name', 'vendor:id,uuid,name'])
            ->when($search !== '', fn ($query) => $query->where('name', 'ilike', '%'.$search.'%'))
            ->when(in_array($statusFilter, ['draft', 'committed', 'cancelled'], true),
                fn ($query) => $query->where('status', $statusFilter))
            ->when($categoryFilter !== '' && $categoryFilter !== 'all',
                fn ($query) => $query->whereHas('category', fn ($category) => $category->where('uuid', $categoryFilter)))
            ->orderBy('name')->orderBy('id')
            ->paginate(20)->withQueryString();

        return Inertia::render('weddings/finance/budget/index', [
            'items' => $items->through(fn (BudgetItem $item): array => [
                'uuid' => $item->uuid,
                'name' => $item->name,
                'status' => $item->status->value,
                'estimatedAmount' => $item->estimated_amount,
                'finalAmount' => $item->final_amount,
                'category' => $item->category?->name,
                'event' => $item->event?->name,
                'vendor' => $item->vendor?->name,
                'paymentTermsCount' => $item->payment_terms_count ?? $item->paymentTerms()->count(),
            ]),
            'filters' => [
                'search' => $search,
                'status' => $statusFilter,
                'category' => $categoryFilter,
            ],
            'categories' => $wedding->budgetCategories()->orderBy('sort_order')->get(['uuid', 'name']),
            'events' => $wedding->events()->orderBy('event_date')->get(['uuid', 'name']),
            'vendors' => $wedding->vendors()->orderBy('name')->get(['uuid', 'name']),
            'summary' => $calculations->budgetSummary($wedding),
            'canEdit' => $request->user()->can('editOperationalData', $wedding),
            'canExport' => $request->user()->can('export', $wedding),
        ]);
    }

    public function store(StoreBudgetItemRequest $request, Wedding $wedding): RedirectResponse
    {
        $data = $request->budgetItemData();

        $item = $wedding->budgetItems()->create([
            'budget_category_id' => $this->resolveId($wedding, 'budget_categories', $data['budget_category_uuid']),
            'wedding_event_id' => $this->resolveId($wedding, 'wedding_events', $data['wedding_event_uuid']),
            'vendor_id' => $this->resolveId($wedding, 'vendors', $data['vendor_uuid']),
            'name' => $data['name'],
            'estimated_amount' => $data['estimated_amount'],
            'final_amount' => $data['final_amount'],
            'status' => 'draft',
            'notes' => $data['notes'],
        ]);

        $wedding->activityLogs()->create([
            'actor_id' => $request->user()->id,
            'action' => 'budget.item.created',
            'subject_type' => BudgetItem::class,
            'subject_id' => $item->id,
            'changes' => ['name' => $item->name, 'estimated_amount' => $item->estimated_amount],
        ]);

        return to_route('weddings.finance.budget.show', [$wedding, $item])->with('success', 'Item anggaran berhasil ditambahkan.');
    }

    public function show(Request $request, Wedding $wedding, BudgetItem $budget_item): Response
    {
        abort_unless($budget_item->wedding_id === $wedding->id, 404);
        abort_unless($request->user()->can('view', $budget_item), 403);

        $budget_item->load(['category:id,uuid,name,color', 'event:id,uuid,name', 'vendor:id,uuid,name']);
        $terms = $budget_item->paymentTerms()->orderBy('sort_order')->orderBy('id')->get();

        // Payment terms carry attachments only (no custom-field module), per D-027/D-028.
        $termAttachments = collect(
            \App\Support\CustomFields\SubjectCustomFields::attachmentsForSubjects($wedding, \App\Enums\AttachmentModule::PaymentTerm, $terms)
        )->mapWithKeys(fn ($list, $termId): array => [
            (string) $terms->firstWhere('id', (int) $termId)?->uuid => $list,
        ])->all();

        return Inertia::render('weddings/finance/budget/show', [
            'item' => [
                'uuid' => $budget_item->uuid,
                'name' => $budget_item->name,
                'status' => $budget_item->status->value,
                'estimatedAmount' => $budget_item->estimated_amount,
                'finalAmount' => $budget_item->final_amount,
                'notes' => $budget_item->notes,
                'category' => $budget_item->category?->name,
                'event' => $budget_item->event?->name,
                'categoryUuid' => $budget_item->category?->uuid,
                'eventUuid' => $budget_item->event?->uuid,
                'vendor' => $budget_item->vendor?->name,
                'vendorUuid' => $budget_item->vendor?->uuid,
                'sourceScenarioName' => $budget_item->source_scenario_name,
            ],
            'paymentTerms' => $terms->map(fn ($term): array => [
                'uuid' => $term->uuid,
                'name' => $term->name,
                'sortOrder' => $term->sort_order,
                'plannedAmount' => $term->planned_amount,
                'dueDate' => $term->due_date?->toDateString(),
                'actualPaidAmount' => $term->actual_paid_amount,
                'paidAt' => $term->paid_at?->toDateString(),
                'status' => $term->status->value,
                'notes' => $term->notes,
            ]),
            'paidTotal' => $terms->where('status.value', '!=', 'cancelled')->sum('actual_paid_amount'),
            'categories' => $wedding->budgetCategories()->where('is_active', true)->orderBy('sort_order')->get(['uuid', 'name']),
            'events' => $wedding->events()->orderBy('event_date')->get(['uuid', 'name']),
            'vendors' => $wedding->vendors()->orderBy('name')->get(['uuid', 'name']),
            'customFields' => \App\Support\CustomFields\SubjectCustomFields::payload($wedding, \App\Enums\CustomFieldModule::BudgetItem, $budget_item),
            'attachments' => \App\Support\CustomFields\SubjectCustomFields::attachments($wedding, \App\Enums\AttachmentModule::BudgetItem, $budget_item),
            'termAttachments' => $termAttachments,
            'canEdit' => $request->user()->can('update', $budget_item),
        ]);
    }

    public function update(UpdateBudgetItemRequest $request, Wedding $wedding, BudgetItem $budget_item): RedirectResponse
    {
        abort_unless($budget_item->wedding_id === $wedding->id, 404);

        $data = $request->budgetItemData();
        $before = ['name' => $budget_item->name, 'estimated_amount' => $budget_item->estimated_amount, 'final_amount' => $budget_item->final_amount];

        $budget_item->update([
            'budget_category_id' => $this->resolveId($wedding, 'budget_categories', $data['budget_category_uuid']),
            'wedding_event_id' => $this->resolveId($wedding, 'wedding_events', $data['wedding_event_uuid']),
            'vendor_id' => $this->resolveId($wedding, 'vendors', $data['vendor_uuid']),
            'name' => $data['name'],
            'estimated_amount' => $data['estimated_amount'],
            'final_amount' => $data['final_amount'],
            'notes' => $data['notes'],
        ]);

        $wedding->activityLogs()->create([
            'actor_id' => $request->user()->id,
            'action' => 'budget.item.updated',
            'subject_type' => BudgetItem::class,
            'subject_id' => $budget_item->id,
            'changes' => ['before' => $before, 'after' => ['name' => $budget_item->name, 'estimated_amount' => $budget_item->estimated_amount, 'final_amount' => $budget_item->final_amount]],
        ]);

        return to_route('weddings.finance.budget.show', [$wedding, $budget_item])->with('success', 'Item anggaran berhasil diperbarui.');
    }

    public function commit(CommitBudgetItemRequest $request, Wedding $wedding, BudgetItem $budget_item, CommitBudgetItemAction $committer): RedirectResponse
    {
        abort_unless($budget_item->wedding_id === $wedding->id, 404);

        try {
            $committer->execute($request->user(), $budget_item, $request->finalAmount());
        } catch (RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return to_route('weddings.finance.budget.show', [$wedding, $budget_item])->with('success', 'Item anggaran berhasil dikomit.');
    }

    public function cancel(Request $request, Wedding $wedding, BudgetItem $budget_item, CancelBudgetItemAction $canceller): RedirectResponse
    {
        abort_unless($budget_item->wedding_id === $wedding->id, 404);
        abort_unless($request->user()->can('cancel', $budget_item), 403);

        $canceller->execute($request->user(), $budget_item);

        return to_route('weddings.finance.budget.index', $wedding)->with('success', 'Item anggaran dibatalkan dan tetap tersimpan sebagai riwayat.');
    }

    public function destroy(Request $request, Wedding $wedding, BudgetItem $budget_item): RedirectResponse
    {
        abort_unless($budget_item->wedding_id === $wedding->id, 404);
        abort_unless($request->user()->can('delete', $budget_item), 403);

        if ($budget_item->status !== BudgetItemStatus::Draft) {
            return back()->with('error', 'Hanya item draf tanpa riwayat pembayaran yang dapat dihapus. Gunakan batalkan sebagai gantinya.');
        }

        $hasPayments = $budget_item->paymentTerms()->where('actual_paid_amount', '>', 0)->exists();

        if ($hasPayments) {
            return back()->with('error', 'Item dengan riwayat pembayaran tidak dapat dihapus. Gunakan batalkan sebagai gantinya.');
        }

        $budget_item->delete();

        return to_route('weddings.finance.budget.index', $wedding)->with('success', 'Item anggaran berhasil dihapus.');
    }

    private function resolveId(Wedding $wedding, string $table, ?string $uuid): ?int
    {
        if ($uuid === null) {
            return null;
        }

        $id = $wedding->getConnection()->table($table)
            ->where('wedding_id', $wedding->id)
            ->where('uuid', $uuid)
            ->value('id');

        abort_unless($id !== null, 404);

        return (int) $id;
    }
}
