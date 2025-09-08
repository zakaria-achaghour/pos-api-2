<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenant
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
         $user = auth('api')->user();

        // SuperAdmin bypass
        if ($user && $user->hasRole('SuperAdmin')) {
            return $next($request);
        }

        abort_unless($user && $user->restaurant_id, 403, 'Tenant not set');
        return $next($request);
    }
}
