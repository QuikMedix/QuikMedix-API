<?php

namespace App\Http\Middleware;

use App\Http\Controllers\LexaAdmin;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    /**
     * Stops blocked or deactivated accounts, and staff of a blocked pharmacy.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->isblocked_or_isactive()) {
            abort(403, LexaAdmin::$err_act_ban);
        }

        return $next($request);
    }
}
