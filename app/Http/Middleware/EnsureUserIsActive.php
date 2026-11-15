<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Support\AccessMessages;

class EnsureUserIsActive
{
    /**
     * Stops blocked or deactivated accounts, and staff of a blocked pharmacy.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->isblocked_or_isactive()) {
            abort(403, AccessMessages::ACCOUNT_INACTIVE);
        }

        return $next($request);
    }
}
