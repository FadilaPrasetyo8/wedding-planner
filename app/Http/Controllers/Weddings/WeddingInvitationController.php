<?php

namespace App\Http\Controllers\Weddings;

use App\Actions\Weddings\CreateWeddingInvitationAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Weddings\StoreWeddingInvitationRequest;
use App\Models\Wedding;
use App\Models\WeddingInvitation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class WeddingInvitationController extends Controller
{
    public function store(
        StoreWeddingInvitationRequest $request,
        Wedding $wedding,
        CreateWeddingInvitationAction $createInvitation,
    ): RedirectResponse {
        $createInvitation->execute($wedding, $request->user(), $request->invitationData());

        return back()->with('status', 'invitation-sent');
    }

    public function destroy(Request $request, Wedding $wedding, WeddingInvitation $invitation): RedirectResponse
    {
        $request->user()->cannot('manageMembers', $wedding) && abort(403);
        abort_unless($invitation->wedding_id === $wedding->id, 404);
        abort_unless($invitation->isPending(), 410);

        $invitation->update(['cancelled_at' => now()]);

        return back()->with('status', 'invitation-cancelled');
    }
}
