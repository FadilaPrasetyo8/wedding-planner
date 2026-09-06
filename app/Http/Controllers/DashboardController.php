<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $weddings = $request->user()->activeWeddings()
            ->orderBy('weddings.id')
            ->get();

        if ($weddings->isEmpty()) {
            return to_route('weddings.create');
        }

        $preferredUuid = $request->session()->get('active_wedding_uuid');
        $wedding = $weddings->firstWhere('uuid', $preferredUuid) ?? $weddings->first();

        return to_route('weddings.show', $wedding);
    }
}
