<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The dashboard's lists, reports and printouts load whole result sets at once, so they run
 * without PHP's memory limit. This is the one place that says so; narrow it to the routes
 * that need it once memory use has been measured in production.
 */
class LiftMemoryLimit
{
    public function handle(Request $request, Closure $next): Response
    {
        ini_set('memory_limit', '-1');

        return $next($request);
    }
}
