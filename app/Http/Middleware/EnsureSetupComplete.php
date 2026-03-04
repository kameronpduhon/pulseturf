<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSetupComplete
{
    /**
     * Handle an incoming request.
     *
     * Redirect to /setup if the authenticated user does not have an active business.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->hasActiveBusiness()) {
            return redirect()->route('setup');
        }

        return $next($request);
    }
}
