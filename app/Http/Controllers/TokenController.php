<?php

namespace App\Http\Controllers;

use App\Support\ChatConversations;
use Illuminate\Http\Request;

class TokenController extends Controller
{
    /**
     * The token the chat page signs in to Twilio Conversations with.
     */
    public function generate(Request $request, ChatConversations $chat)
    {
        $identity = $request->input('identity');
        // A chat token lets its holder read and write as that identity, so it is only issued to that person.
        abort_unless((string) $identity === $request->attributes->get('chat_viewer'), 403, 'This chat identity is not yours.');
        abort_unless($chat->isConfigured(), 503, 'Chat is not set up.');

        return response()->json([
            'identity' => $identity,
            'token' => $chat->token((string) $identity),
        ]);
    }
}
