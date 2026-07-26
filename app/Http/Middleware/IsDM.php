<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IsDM
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! session('selected_campaign_id')) {
            return redirect()->route('dashboard');
        }

        if (session('selected_campaign_role') !== 'dm') {
            return redirect()->route('dashboard');
        }

        return $next($request);
    }
}
