<?php

namespace App\Http\Middleware;

use App\Support\AccessMessages;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAllowed
{
    /**
     * Lets the request through for accounts with the ability, as defined in AuthServiceProvider:
     * `allowed:admin`. Unlike Laravel's `can`, a refusal shows the application's own message.
     */
    public function handle(Request $request, Closure $next, string $ability): Response
    {
        abort_unless($request->user()->can($ability), 403, AccessMessages::NO_PERMISSION);

        return $next($request);
    }
}
