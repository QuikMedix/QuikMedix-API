<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureApiAccountIsActive
{
    /**
     * Refuses blocked or unapproved accounts (and staff of a blocked pharmacy) on the mobile API,
     * and keeps the access token of an active account alive for another week.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()->isblocked_or_isactive()) {
            return response()->json([
                'message' => 'You cannot open this page',
                'errors' => 'Forbidden',
            ], 403);
        }

        $request->user()->extendTokenExpiry();

        return $next($request);
    }
}
