<?php

namespace App\Actions\Weddings;

use App\Models\User;
use App\Models\WeddingInvitation;
use Illuminate\Support\Facades\DB;

class RespondToWeddingInvitationAction
{
    public function execute(WeddingInvitation $invitation, User $user, bool $accept): void
    {
        DB::transaction(function () use ($invitation, $user, $accept): void {
            $invitation = WeddingInvitation::query()->lockForUpdate()->findOrFail($invitation->id);
            abort_unless($invitation->isPending(), 410, 'Undangan tidak lagi berlaku.');
            abort_unless(mb_strtolower($user->email) === $invitation->email, 403);

            if (! $accept) {
                $invitation->update(['declined_at' => now()]);

                return;
            }

            $membership = $invitation->wedding->memberships()
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->first();

            if ($membership) {
                $membership->update([
                    'role' => $invitation->role,
                    'joined_at' => now(),
                    'left_at' => null,
                ]);
            } else {
                $invitation->wedding->memberships()->create([
                    'user_id' => $user->id,
                    'role' => $invitation->role,
                    'joined_at' => now(),
                ]);
            }

            $invitation->update(['accepted_at' => now()]);
        });
    }
}
