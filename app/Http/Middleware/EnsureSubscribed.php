<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSubscribed
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user?->isOnTrial() && ! $user?->subscribed()) {
            return redirect()->route('billing');
        }

        return $next($request);
    }
}
