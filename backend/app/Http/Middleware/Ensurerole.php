<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

// Usage: ->middleware('role:admin')  or  ->middleware('role:admin,staff')
// Skip this file if you already have an equivalent RBAC middleware.
class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (!$user || !$user->is_active || !in_array($user->role, $roles, true)) {
            abort(403, 'You do not have permission to do that.');
        }

        return $next($request);
    }
}