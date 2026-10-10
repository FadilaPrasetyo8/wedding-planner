<?php

namespace App\Http\Controllers\Finance;

use App\Actions\Finance\RecordSavingContributionAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\StoreSavingContributionRequest;
use App\Http\Requests\Finance\UpdateSavingContributionRequest;
use App\Models\SavingContribution;
use App\Models\Wedding;
use App\Support\Finance\FinanceCalculations;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SavingContributionController extends Controller
{
    public function index(Request $request, Wedding $wedding, FinanceCalculations $calculations): Response
    {
        $contributions = $wedding->savingContributions()
            ->with(['goal:id,uuid,name', 'contributor:id,uuid,label'])
            ->orderByDesc('contributed_on')->orderByDesc('id')
            ->paginate(20)->withQueryString();

        return Inertia::render('weddings/finance/savings/contributions', [
            'contributions' => $contributions->through(fn (SavingContribution $contribution): array => [
                'uuid' => $contribution->uuid,
                'goal' => $contribution->goal->name,
                'contributor' => $contribution->contributor->label,
                'amount' => $contribution->amount,
                'contributedOn' => $contribution->contributed_on?->toDateString(),
                'source' => $contribution->source,
                'notes' => $contribution->notes,
            ]),
            'goals' => $wedding->savingGoals()->where('is_active', true)->orderBy('id')->get(['uuid', 'name']),
            'contributors' => $wedding->savingContributors()->where('is_active', true)->orderBy('sort_order')->get(['uuid', 'label']),
            'summary' => $calculations->savingsSummary($wedding),
            'canEdit' => $request->user()->can('editOperationalData', $wedding),
        ]);
    }

    public function store(StoreSavingContributionRequest $request, Wedding $wedding, RecordSavingContributionAction $recorder): RedirectResponse
    {
        $data = $request->contributionData();

        $goalId = $this->resolveId($wedding, 'saving_goals', $data['saving_goal_uuid']);
        $contributorId = $this->resolveId($wedding, 'saving_contributors', $data['saving_contributor_uuid']);

        $recorder->create($request->user(), [
            'wedding_id' => $wedding->id,
            'saving_goal_id' => $goalId,
            'saving_contributor_id' => $contributorId,
            'amount' => $data['amount'],
            'contributed_on' => $data['contributed_on'],
            'source' => $data['source'],
            'notes' => $data['notes'],
        ]);

        return to_route('weddings.finance.savings.contributions', $wedding)->with('success', 'Setoran berhasil dicatat.');
    }

    public function update(UpdateSavingContributionRequest $request, Wedding $wedding, SavingContribution $saving_contribution, RecordSavingContributionAction $recorder): RedirectResponse
    {
        abort_unless($saving_contribution->wedding_id === $wedding->id, 404);

        $recorder->update($request->user(), $saving_contribution, $request->contributionData());

        return to_route('weddings.finance.savings.contributions', $wedding)->with('success', 'Setoran berhasil diperbarui.');
    }

    public function destroy(Request $request, Wedding $wedding, SavingContribution $saving_contribution, RecordSavingContributionAction $recorder): RedirectResponse
    {
        abort_unless($saving_contribution->wedding_id === $wedding->id, 404);
        abort_unless($request->user()->can('delete', $saving_contribution), 403);

        $recorder->delete($request->user(), $saving_contribution);

        return to_route('weddings.finance.savings.contributions', $wedding)->with('success', 'Setoran berhasil dihapus.');
    }

    private function resolveId(Wedding $wedding, string $table, string $uuid): int
    {
        $id = $wedding->getConnection()->table($table)
            ->where('wedding_id', $wedding->id)
            ->where('uuid', $uuid)
            ->value('id');

        abort_unless($id !== null, 404);

        return (int) $id;
    }
}
