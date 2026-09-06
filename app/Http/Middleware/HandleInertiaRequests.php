<?php

namespace App\Http\Middleware;

use App\Enums\WeddingMemberRole;
use App\Models\Wedding;
use App\Models\WeddingMember;
use App\Support\ActiveWeddingContext;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $context = app(ActiveWeddingContext::class);
        $routeWedding = $request->route('wedding');

        if (! $context->hasWedding() && $routeWedding instanceof Wedding && $request->user()) {
            $membership = $routeWedding->memberships()
                ->where('user_id', $request->user()->id)
                ->whereNull('left_at')
                ->first();

            if ($membership) {
                $context->set($routeWedding, $membership);
            }
        }

        $membership = $context->hasWedding() ? $context->membership() : null;
        $role = $membership?->role;

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $request->user(),
            ],
            'weddings' => fn () => $request->user()?->weddingMemberships()
                ->active()
                ->with('wedding')
                ->whereHas('wedding')
                ->get()
                ->sortBy(fn (WeddingMember $membership): string => $membership->wedding->name)
                ->values()
                ->map(fn (WeddingMember $membership): array => [
                    'uuid' => $membership->wedding->uuid,
                    'name' => $membership->wedding->name,
                    'partnerNames' => $membership->wedding->partner_one_name.' & '.$membership->wedding->partner_two_name,
                    'role' => $membership->role->value,
                ]) ?? [],
            'activeWedding' => $context->hasWedding() ? [
                'uuid' => $context->wedding()->uuid,
                'name' => $context->wedding()->name,
                'partnerNames' => $context->wedding()->partner_one_name.' & '.$context->wedding()->partner_two_name,
                'weddingDate' => $context->wedding()->wedding_date->toDateString(),
            ] : null,
            'activeMembership' => $membership ? ['role' => $role->value] : null,
            'capabilities' => $membership ? [
                'manageMembers' => $role === WeddingMemberRole::Owner,
                'editWedding' => $role === WeddingMemberRole::Owner,
                'editOperationalData' => in_array($role, [WeddingMemberRole::Owner, WeddingMemberRole::Editor], true),
            ] : null,
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }
}
