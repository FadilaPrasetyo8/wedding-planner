<?php

namespace App\Http\Middleware;

use App\Models\Wedding;
use App\Support\ActiveWeddingContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureWeddingMembership
{
    public function __construct(private ActiveWeddingContext $context) {}

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $wedding = $request->route('wedding');

        abort_unless($wedding instanceof Wedding, 404);

        $membership = $wedding->memberships()
            ->where('user_id', $request->user()->id)
            ->whereNull('left_at')
            ->first();

        abort_unless($membership !== null, 403);

        $this->context->set($wedding, $membership);
        $request->session()->put('active_wedding_uuid', $wedding->uuid);

        return $next($request);
    }
}
