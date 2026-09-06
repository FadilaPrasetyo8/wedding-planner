<?php

namespace App\Http\Controllers\Weddings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Weddings\DeleteWeddingRequest;
use App\Http\Requests\Weddings\UpdateWeddingRequest;
use App\Models\Wedding;
use Illuminate\Http\RedirectResponse;

class WeddingSettingsController extends Controller
{
    public function update(UpdateWeddingRequest $request, Wedding $wedding): RedirectResponse
    {
        $wedding->update($request->validated());

        return back()->with('status', 'wedding-updated');
    }

    public function destroy(DeleteWeddingRequest $request, Wedding $wedding): RedirectResponse
    {
        $wedding->activityLogs()->create([
            'actor_id' => $request->user()->id,
            'action' => 'wedding.deleted',
            'subject_type' => Wedding::class,
            'subject_id' => $wedding->id,
        ]);
        $wedding->delete();

        if ($request->session()->get('active_wedding_uuid') === $wedding->uuid) {
            $request->session()->forget('active_wedding_uuid');
        }

        return to_route('dashboard');
    }
}
