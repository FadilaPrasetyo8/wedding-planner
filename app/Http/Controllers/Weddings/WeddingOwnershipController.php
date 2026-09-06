<?php

namespace App\Http\Controllers\Weddings;

use App\Actions\Weddings\TransferWeddingOwnershipAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Weddings\TransferWeddingOwnershipRequest;
use App\Models\Wedding;
use Illuminate\Http\RedirectResponse;

class WeddingOwnershipController extends Controller
{
    public function __invoke(
        TransferWeddingOwnershipRequest $request,
        Wedding $wedding,
        TransferWeddingOwnershipAction $transfer,
    ): RedirectResponse {
        $transfer->execute($wedding, $request->user(), $request->integer('membership_id'));

        return back()->with('status', 'ownership-transferred');
    }
}
