<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\StoreSavingGoalRequest;
use App\Http\Requests\Finance\UpdateSavingGoalRequest;
use App\Models\SavingGoal;
use App\Models\Wedding;
use App\Support\Finance\FinanceCalculations;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SavingGoalController extends Controller
{
    public function index(Request $request, Wedding $wedding, FinanceCalculations $calculations): Response
    {
        $goals = $wedding->savingGoals()
            ->withSum('contributions as contributed_sum', 'amount')
            ->orderBy('id')
            ->get(['uuid', 'name', 'target_amount', 'target_date', 'is_active', 'notes']);

        return Inertia::render('weddings/finance/savings/index', [
            'goals' => $goals->map(fn (SavingGoal $goal): array => [
                'uuid' => $goal->uuid,
                'name' => $goal->name,
                'targetAmount' => $goal->target_amount,
                'targetDate' => $goal->target_date?->toDateString(),
                'isActive' => $goal->is_active,
                'notes' => $goal->notes,
                'collectedAmount' => (int) ($goal->contributed_sum ?? 0),
                'progressPercentage' => $goal->target_amount === 0 ? 0 : (int) round(((int) ($goal->contributed_sum ?? 0) / $goal->target_amount) * 100),
            ]),
            'summary' => $calculations->savingsSummary($wedding),
            'canEdit' => $request->user()->can('editOperationalData', $wedding),
        ]);
    }

    public function store(StoreSavingGoalRequest $request, Wedding $wedding): RedirectResponse
    {
        $goal = $wedding->savingGoals()->create([
            ...$request->goalData(),
            'is_active' => true,
        ]);

        $wedding->activityLogs()->create([
            'actor_id' => $request->user()->id,
            'action' => 'savings.goal.created',
            'subject_type' => SavingGoal::class,
            'subject_id' => $goal->id,
            'changes' => ['name' => $goal->name, 'target_amount' => $goal->target_amount],
        ]);

        return to_route('weddings.finance.savings.index', $wedding)->with('success', 'Target tabungan berhasil dibuat.');
    }

    public function update(UpdateSavingGoalRequest $request, Wedding $wedding, SavingGoal $saving_goal): RedirectResponse
    {
        abort_unless($saving_goal->wedding_id === $wedding->id, 404);

        $before = ['name' => $saving_goal->name, 'target_amount' => $saving_goal->target_amount, 'is_active' => $saving_goal->is_active];
        $saving_goal->update($request->goalData());

        $wedding->activityLogs()->create([
            'actor_id' => $request->user()->id,
            'action' => 'savings.goal.updated',
            'subject_type' => SavingGoal::class,
            'subject_id' => $saving_goal->id,
            'changes' => ['before' => $before, 'after' => ['name' => $saving_goal->name, 'target_amount' => $saving_goal->target_amount, 'is_active' => $saving_goal->is_active]],
        ]);

        return to_route('weddings.finance.savings.index', $wedding)->with('success', 'Target tabungan berhasil diperbarui.');
    }

    public function destroy(Request $request, Wedding $wedding, SavingGoal $saving_goal): RedirectResponse
    {
        abort_unless($saving_goal->wedding_id === $wedding->id, 404);
        abort_unless($request->user()->can('delete', $saving_goal), 403);

        if ($saving_goal->contributions()->exists()) {
            return back()->with('error', 'Target dengan riwayat setoran tidak dapat dihapus. Nonaktifkan sebagai gantinya.');
        }

        $saving_goal->delete();

        return to_route('weddings.finance.savings.index', $wedding)->with('success', 'Target tabungan berhasil dihapus.');
    }
}
