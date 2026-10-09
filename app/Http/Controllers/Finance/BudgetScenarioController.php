<?php

namespace App\Http\Controllers\Finance;

use App\Actions\Finance\ApplyBudgetScenarioAction;
use App\Actions\Finance\CopyBudgetScenarioAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\ApplyBudgetScenarioRequest;
use App\Http\Requests\Finance\CopyBudgetScenarioRequest;
use App\Http\Requests\Finance\StoreBudgetScenarioRequest;
use App\Http\Requests\Finance\UpdateBudgetScenarioRequest;
use App\Models\BudgetScenario;
use App\Models\Wedding;
use App\Support\Finance\FinanceCalculations;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

class BudgetScenarioController extends Controller
{
    public function index(Request $request, Wedding $wedding, FinanceCalculations $calculations): Response
    {
        $scenarios = $wedding->budgetScenarios()
            ->withSum(['scenarioItems as active_amount_sum' => fn ($query) => $query->where('is_active', true)], 'amount')
            ->withCount(['scenarioItems as active_items_count' => fn ($query) => $query->where('is_active', true)])
            ->orderBy('sort_order')->orderBy('id')
            ->get(['uuid', 'name', 'description', 'sort_order', 'is_active']);

        return Inertia::render('weddings/finance/scenarios/index', [
            'scenarios' => $scenarios->map(fn (BudgetScenario $scenario): array => [
                'uuid' => $scenario->uuid,
                'name' => $scenario->name,
                'description' => $scenario->description,
                'isActive' => $scenario->is_active,
                'totalAmount' => (int) ($scenario->active_amount_sum ?? 0),
                'itemCount' => (int) ($scenario->active_items_count ?? 0),
            ]),
            'canEdit' => $request->user()->can('editOperationalData', $wedding),
        ]);
    }

    public function store(StoreBudgetScenarioRequest $request, Wedding $wedding): RedirectResponse
    {
        $maxOrder = (int) $wedding->budgetScenarios()->max('sort_order');

        $scenario = $wedding->budgetScenarios()->create([
            ...$request->scenarioData(),
            'sort_order' => $maxOrder + 10,
            'is_active' => true,
        ]);

        $wedding->activityLogs()->create([
            'actor_id' => $request->user()->id,
            'action' => 'budget.scenario.created',
            'subject_type' => BudgetScenario::class,
            'subject_id' => $scenario->id,
            'changes' => ['name' => $scenario->name],
        ]);

        return to_route('weddings.finance.scenarios.show', [$wedding, $scenario])->with('success', 'Skenario berhasil dibuat.');
    }

    public function show(Request $request, Wedding $wedding, BudgetScenario $scenario, FinanceCalculations $calculations): Response
    {
        $this->authorizeScenario($request, $wedding, $scenario);

        $items = $scenario->scenarioItems()
            ->with(['category:id,uuid,name,color', 'event:id,uuid,name'])
            ->orderBy('sort_order')->orderBy('id')
            ->get();

        return Inertia::render('weddings/finance/scenarios/show', [
            'scenario' => [
                'uuid' => $scenario->uuid,
                'name' => $scenario->name,
                'description' => $scenario->description,
                'isActive' => $scenario->is_active,
                'totalAmount' => $calculations->scenarioTotal($wedding, $scenario->id),
            ],
            'items' => $items->map(fn ($item): array => [
                'uuid' => $item->uuid,
                'name' => $item->name,
                'amount' => $item->amount,
                'notes' => $item->notes,
                'isActive' => $item->is_active,
                'category' => $item->category?->name,
                'event' => $item->event?->name,
            ]),
            'categories' => $wedding->budgetCategories()->where('is_active', true)->orderBy('sort_order')->get(['uuid', 'name']),
            'events' => $wedding->events()->orderBy('event_date')->get(['uuid', 'name']),
            'hasBeenApplied' => app(ApplyBudgetScenarioAction::class)->hasBeenApplied($scenario),
            'canEdit' => $request->user()->can('editOperationalData', $wedding),
        ]);
    }

    public function update(UpdateBudgetScenarioRequest $request, Wedding $wedding, BudgetScenario $scenario): RedirectResponse
    {
        $before = ['name' => $scenario->name, 'is_active' => $scenario->is_active];
        $scenario->update($request->scenarioData());

        $wedding->activityLogs()->create([
            'actor_id' => $request->user()->id,
            'action' => 'budget.scenario.updated',
            'subject_type' => BudgetScenario::class,
            'subject_id' => $scenario->id,
            'changes' => ['before' => $before, 'after' => ['name' => $scenario->name, 'is_active' => $scenario->is_active]],
        ]);

        return to_route('weddings.finance.scenarios.show', [$wedding, $scenario])->with('success', 'Skenario berhasil diperbarui.');
    }

    public function copy(CopyBudgetScenarioRequest $request, Wedding $wedding, BudgetScenario $scenario, CopyBudgetScenarioAction $copier): RedirectResponse
    {
        $copy = $copier->execute($request->user(), $scenario, $request->newScenarioName());

        return to_route('weddings.finance.scenarios.show', [$wedding, $copy])->with('success', 'Skenario berhasil disalin sebagai draf baru.');
    }

    public function apply(ApplyBudgetScenarioRequest $request, Wedding $wedding, BudgetScenario $scenario, ApplyBudgetScenarioAction $applier): RedirectResponse
    {
        try {
            $applier->execute($request->user(), $scenario, $request->confirmed());
        } catch (RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return to_route('weddings.finance.budget.index', $wedding)->with('success', 'Skenario berhasil diterapkan sebagai item anggaran draf.');
    }

    public function destroy(Request $request, Wedding $wedding, BudgetScenario $scenario): RedirectResponse
    {
        abort_unless($request->user()->can('delete', $scenario), 403);

        $scenario->delete();

        return to_route('weddings.finance.scenarios.index', $wedding)->with('success', 'Skenario berhasil dihapus.');
    }

    private function authorizeScenario(Request $request, Wedding $wedding, BudgetScenario $scenario): void
    {
        abort_unless($scenario->wedding_id === $wedding->id, 404);
        abort_unless($request->user()->can('view', $scenario), 403);
    }
}
