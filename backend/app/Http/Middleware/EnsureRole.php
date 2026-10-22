<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Lets a request through only if the logged-in user has the given role.
 * Used in routes as ->middleware('role:agent') or ->middleware('role:manager').
 */
class EnsureRole
{
    public function handle(Request $request, Closure $next, string $role): Response
    {
        if ($request->user()?->role !== $role) {
            abort(403, 'You do not have access to this page.');
        }

        return $next($request);
    }
}
