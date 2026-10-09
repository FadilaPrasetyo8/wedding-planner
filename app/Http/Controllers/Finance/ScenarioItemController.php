<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\StoreScenarioItemRequest;
use App\Http\Requests\Finance\UpdateScenarioItemRequest;
use App\Models\BudgetScenario;
use App\Models\ScenarioItem;
use App\Models\Wedding;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ScenarioItemController extends Controller
{
    public function store(StoreScenarioItemRequest $request, Wedding $wedding, BudgetScenario $scenario): RedirectResponse
    {
        $this->authorizeSameWedding($wedding, $scenario);

        $maxOrder = (int) $scenario->scenarioItems()->max('sort_order');
        $data = $request->scenarioItemData();

        $scenario->scenarioItems()->create([
            'wedding_id' => $wedding->id,
            'budget_category_id' => $this->resolveId($wedding, 'budget_categories', $data['budget_category_uuid']),
            'wedding_event_id' => $this->resolveId($wedding, 'wedding_events', $data['wedding_event_uuid']),
            'name' => $data['name'],
            'amount' => $data['amount'],
            'notes' => $data['notes'],
            'sort_order' => $maxOrder + 10,
            'is_active' => true,
        ]);

        return to_route('weddings.finance.scenarios.show', [$wedding, $scenario])->with('success', 'Alokasi berhasil ditambahkan.');
    }

    public function update(UpdateScenarioItemRequest $request, Wedding $wedding, BudgetScenario $scenario, ScenarioItem $scenario_item): RedirectResponse
    {
        $this->authorizeSameWedding($wedding, $scenario, $scenario_item);

        $data = $request->scenarioItemData();

        $scenario_item->update([
            'budget_category_id' => $this->resolveId($wedding, 'budget_categories', $data['budget_category_uuid']),
            'wedding_event_id' => $this->resolveId($wedding, 'wedding_events', $data['wedding_event_uuid']),
            'name' => $data['name'],
            'amount' => $data['amount'],
            'notes' => $data['notes'],
            'is_active' => $data['is_active'],
        ]);

        return to_route('weddings.finance.scenarios.show', [$wedding, $scenario])->with('success', 'Alokasi berhasil diperbarui.');
    }

    public function destroy(Request $request, Wedding $wedding, BudgetScenario $scenario, ScenarioItem $scenario_item): RedirectResponse
    {
        abort_unless($scenario_item->wedding_id === $wedding->id && $scenario_item->budget_scenario_id === $scenario->id, 404);
        abort_unless($request->user()->can('delete', $scenario_item), 403);

        $scenario_item->delete();

        return to_route('weddings.finance.scenarios.show', [$wedding, $scenario])->with('success', 'Alokasi berhasil dihapus.');
    }

    private function authorizeSameWedding(Wedding $wedding, BudgetScenario $scenario, ?ScenarioItem $item = null): void
    {
        abort_unless($scenario->wedding_id === $wedding->id, 404);

        if ($item !== null) {
            abort_unless($item->wedding_id === $wedding->id && $item->budget_scenario_id === $scenario->id, 404);
        }
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
