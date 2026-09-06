<?php

namespace App\Actions\Weddings;

use App\Models\WeddingMember;
use Illuminate\Validation\ValidationException;

class DeactivateWeddingMembershipAction
{
    public function execute(WeddingMember $membership): void
    {
        if ($membership->isOwner()) {
            throw ValidationException::withMessages([
                'membership' => 'Pemilik harus memindahkan kepemilikan sebelum keluar atau dihapus.',
            ]);
        }

        $membership->update(['left_at' => now()]);
    }
}
