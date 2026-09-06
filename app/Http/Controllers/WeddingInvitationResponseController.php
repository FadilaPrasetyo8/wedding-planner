<?php

namespace App\Http\Controllers;

use App\Actions\Weddings\RespondToWeddingInvitationAction;
use App\Models\WeddingInvitation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WeddingInvitationResponseController extends Controller
{
    public function show(Request $request, string $token): Response
    {
        $invitation = $this->resolve($token);
        abort_unless(mb_strtolower($request->user()->email) === $invitation->email, 403);

        return Inertia::render('invitations/show', [
            'token' => $token,
            'invitation' => [
                'weddingName' => $invitation->wedding->name,
                'partnerNames' => $invitation->wedding->partner_one_name.' & '.$invitation->wedding->partner_two_name,
                'inviterName' => $invitation->inviter->name,
                'role' => $invitation->role->value,
                'expiresAt' => $invitation->expires_at->toIso8601String(),
            ],
        ]);
    }

    public function accept(Request $request, string $token, RespondToWeddingInvitationAction $respond): RedirectResponse
    {
        $invitation = $this->resolve($token, requirePending: false);
        $respond->execute($invitation, $request->user(), true);

        return to_route('weddings.show', $invitation->wedding);
    }

    public function decline(Request $request, string $token, RespondToWeddingInvitationAction $respond): RedirectResponse
    {
        $invitation = $this->resolve($token, requirePending: false);
        $respond->execute($invitation, $request->user(), false);

        return to_route('dashboard');
    }

    private function resolve(string $token, bool $requirePending = true): WeddingInvitation
    {
        $invitation = WeddingInvitation::query()
            ->with(['wedding', 'inviter'])
            ->where('token_hash', hash('sha256', $token))
            ->firstOrFail();

        if ($requirePending) {
            abort_unless($invitation->isPending(), 410, 'Undangan tidak lagi berlaku.');
        }

        abort_if($invitation->wedding->trashed(), 410, 'Ruang pernikahan tidak lagi tersedia.');

        return $invitation;
    }
}
