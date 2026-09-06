<?php

namespace App\Http\Controllers\Weddings;

use App\Http\Controllers\Controller;
use App\Models\Wedding;
use App\Support\ActiveWeddingContext;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WeddingOverviewController extends Controller
{
    public function show(Wedding $wedding, ActiveWeddingContext $context): Response
    {
        return Inertia::render('weddings/show', [
            'events' => $wedding->events()
                ->orderBy('event_date')
                ->orderBy('sort_order')
                ->get(['uuid', 'name', 'event_date', 'is_primary']),
            'membershipRole' => $context->membership()->role->value,
        ]);
    }

    public function members(Request $request, Wedding $wedding): Response
    {
        return Inertia::render('weddings/members', [
            'members' => $wedding->activeMemberships()
                ->with('user:id,name,email')
                ->orderBy('joined_at')
                ->get()
                ->map(fn ($membership): array => [
                    'id' => $membership->id,
                    'name' => $membership->user->name,
                    'email' => $membership->user->email,
                    'role' => $membership->role->value,
                    'isCurrentUser' => $membership->user_id === $request->user()->id,
                ]),
            'invitations' => $request->user()->can('manageMembers', $wedding)
                ? $wedding->invitations()->whereNull('accepted_at')->whereNull('declined_at')->whereNull('cancelled_at')
                    ->orderByDesc('created_at')->get(['uuid', 'email', 'role', 'expires_at'])
                : [],
        ]);
    }

    public function settings(Wedding $wedding): Response
    {
        return Inertia::render('weddings/settings', [
            'wedding' => [
                'name' => $wedding->name,
                'partnerOneName' => $wedding->partner_one_name,
                'partnerTwoName' => $wedding->partner_two_name,
                'weddingDate' => $wedding->wedding_date->toDateString(),
                'planningStartDate' => $wedding->planning_start_date?->toDateString(),
                'timezone' => $wedding->timezone,
                'targetBudget' => $wedding->target_budget,
            ],
        ]);
    }
}
