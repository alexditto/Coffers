<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IsAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! auth()->user()->isAdmin()) {
            return redirect()->route('dashboard')->with('error', 'You do not have permission to access this page.');
        }

        return $next($request);
    }
}
