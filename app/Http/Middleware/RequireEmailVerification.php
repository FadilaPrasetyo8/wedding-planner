<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\Middleware\EnsureEmailIsVerified;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireEmailVerification
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('app.require_email_verification', true)) {
            return $next($request);
        }

        return app(EnsureEmailIsVerified::class)->handle($request, $next);
    }
}
