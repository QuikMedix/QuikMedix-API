<?php

namespace App\Http\Middleware;

use App\Enums\Role;
use App\Support\AccessMessages;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * Lets the request through for the listed roles: `role:superadmin,admin,logist`.
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        // A misspelled role on a route is a programming error, not a refusal.
        array_map(Role::from(...), $roles);

        abort_unless($request->user()->hasAnyRole(...$roles), 403, AccessMessages::NO_PERMISSION);

        return $next($request);
    }
}
