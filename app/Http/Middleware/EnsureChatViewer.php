<?php

namespace App\Http\Middleware;

use App\Support\ChatViewer;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Works out who is calling a chat endpoint: the bearer token's owner, or the person the chat
 * page was rendered for (`viewer` and `signature`). Anyone else is refused.
 */
class EnsureChatViewer
{
    public function handle(Request $request, Closure $next): Response
    {
        $viewer = $request->user('api')?->getKey();

        if ($viewer === null && ChatViewer::verify($request->input('viewer'), $request->input('signature'))) {
            $viewer = $request->input('viewer');
        }

        abort_if($viewer === null, 401, 'Unauthenticated.');
        $request->attributes->set('chat_viewer', (string) $viewer);

        return $next($request);
    }
}
