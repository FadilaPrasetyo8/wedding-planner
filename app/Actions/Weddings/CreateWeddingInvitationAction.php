<?php

namespace App\Actions\Weddings;

use App\Models\User;
use App\Models\Wedding;
use App\Models\WeddingInvitation;
use App\Notifications\WeddingInvitationNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

class CreateWeddingInvitationAction
{
    /** @param array{email: string, role: string} $data */
    public function execute(Wedding $wedding, User $inviter, array $data): WeddingInvitation
    {
        $token = Str::random(64);
        $invitation = $wedding->invitations()->create([
            'email' => $data['email'],
            'role' => $data['role'],
            'token_hash' => hash('sha256', $token),
            'invited_by' => $inviter->id,
            'expires_at' => now()->addDays(7),
        ]);

        Notification::route('mail', $invitation->email)
            ->notify(new WeddingInvitationNotification($invitation, $token));

        return $invitation;
    }
}
