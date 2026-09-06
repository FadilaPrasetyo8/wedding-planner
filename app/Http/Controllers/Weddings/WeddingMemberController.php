<?php

namespace App\Http\Controllers\Weddings;

use App\Actions\Weddings\DeactivateWeddingMembershipAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Weddings\UpdateWeddingMemberRequest;
use App\Models\Wedding;
use App\Models\WeddingMember;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class WeddingMemberController extends Controller
{
    public function update(UpdateWeddingMemberRequest $request, Wedding $wedding, WeddingMember $membership): RedirectResponse
    {
        abort_unless($membership->wedding_id === $wedding->id, 404);
        abort_if($membership->isOwner(), 422);
        $membership->update($request->validated());

        return back()->with('status', 'member-updated');
    }

    public function destroy(
        Request $request,
        Wedding $wedding,
        WeddingMember $membership,
        DeactivateWeddingMembershipAction $deactivate,
    ): RedirectResponse {
        $request->user()->cannot('manageMembers', $wedding) && abort(403);
        abort_unless($membership->wedding_id === $wedding->id, 404);
        $deactivate->execute($membership);

        return back()->with('status', 'member-removed');
    }

    public function leave(
        Request $request,
        Wedding $wedding,
        DeactivateWeddingMembershipAction $deactivate,
    ): RedirectResponse {
        $membership = $wedding->activeMemberships()->where('user_id', $request->user()->id)->firstOrFail();
        $deactivate->execute($membership);
        $request->session()->forget('active_wedding_uuid');

        return to_route('dashboard');
    }
}
