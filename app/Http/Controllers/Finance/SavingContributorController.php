<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\StoreSavingContributorRequest;
use App\Http\Requests\Finance\UpdateSavingContributorRequest;
use App\Models\SavingContributor;
use App\Models\Wedding;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SavingContributorController extends Controller
{
    public function index(Request $request, Wedding $wedding): Response
    {
        $contributors = $wedding->savingContributors()
            ->withSum('contributions as contributed_sum', 'amount')
            ->with('membership:id,user_id,wedding_id')
            ->orderBy('sort_order')->orderBy('id')
            ->get();

        return Inertia::render('weddings/finance/savings/contributors', [
            'contributors' => $contributors->map(fn (SavingContributor $contributor): array => [
                'uuid' => $contributor->uuid,
                'label' => $contributor->label,
                'isActive' => $contributor->is_active,
                'linkedMemberName' => $contributor->membership?->user?->name,
                'contributedAmount' => (int) ($contributor->contributed_sum ?? 0),
            ]),
            'memberships' => $wedding->activeMemberships()->with('user:id,name')->orderBy('joined_at')->get()
                ->map(fn ($membership): array => ['id' => $membership->id, 'name' => $membership->user->name]),
            'canEdit' => $request->user()->can('editOperationalData', $wedding),
        ]);
    }

    public function store(StoreSavingContributorRequest $request, Wedding $wedding): RedirectResponse
    {
        $maxOrder = (int) $wedding->savingContributors()->max('sort_order');

        $wedding->savingContributors()->create([
            ...$request->contributorData(),
            'sort_order' => $maxOrder + 10,
            'is_active' => true,
        ]);

        return to_route('weddings.finance.savings.contributors', $wedding)->with('success', 'Penabung berhasil ditambahkan.');
    }

    public function update(UpdateSavingContributorRequest $request, Wedding $wedding, SavingContributor $saving_contributor): RedirectResponse
    {
        abort_unless($saving_contributor->wedding_id === $wedding->id, 404);

        $saving_contributor->update($request->contributorData());

        return to_route('weddings.finance.savings.contributors', $wedding)->with('success', 'Penabung berhasil diperbarui.');
    }

    public function destroy(Request $request, Wedding $wedding, SavingContributor $saving_contributor): RedirectResponse
    {
        abort_unless($saving_contributor->wedding_id === $wedding->id, 404);
        abort_unless($request->user()->can('delete', $saving_contributor), 403);

        if ($saving_contributor->contributions()->exists()) {
            return back()->with('error', 'Penabung dengan riwayat setoran tidak dapat dihapus. Nonaktifkan sebagai gantinya agar riwayat tetap tersimpan.');
        }

        $saving_contributor->delete();

        return to_route('weddings.finance.savings.contributors', $wedding)->with('success', 'Penabung berhasil dihapus.');
    }
}
