<?php

namespace Tests\Support;

use Twilio\Http\Client as HttpClient;
use Twilio\Http\Response;
use Twilio\Rest\Client;

/**
 * A Twilio Conversations service kept in memory. The real SDK builds each request; this answers
 * the ones the application makes (find or create a conversation, list and add participants,
 * list messages, mark read) and records them, so no test reaches Twilio.
 */
trait FakesChatConversations
{
    /** @var list<string> each request as "METHOD path", the path after /v1/Services/IStest */
    protected array $chatRequests = [];

    /** @var array<string, array{sid: string, participants: list<array<string, mixed>>, messages: list<array<string, mixed>>}> conversations by unique name */
    protected array $conversations = [];

    /**
     * @param  array<string, array{participants?: list<array<string, mixed>>, messages?: list<array<string, mixed>>}>  $conversations  what already exists at Twilio, by unique name
     */
    protected function fakeChatConversations(array $conversations = []): void
    {
        config(['app.twilio_sid' => 'ACtest', 'app.twilio_auth_token' => 'test-token', 'app.twilio_apiKey' => 'SKtest', 'app.twilio_apiSecret' => 'secret', 'app.twilio_chatServiceSid' => 'IStest']);
        $this->chatRequests = [];
        $this->conversations = [];
        foreach ($conversations as $name => $conversation) {
            $this->conversations[$name] = ['sid' => 'CH'.count($this->conversations), 'participants' => $conversation['participants'] ?? [], 'messages' => $conversation['messages'] ?? []];
        }
        $handle = fn (string $method, string $url, array $data): Response => $this->answerChatRequest($method, $url, $data);
        $http = new class($handle) implements HttpClient
        {
            public function __construct(private \Closure $handle) {}

            public function request(string $method, string $url, array $params = [], array $data = [], array $headers = [], ?string $user = null, ?string $password = null, ?int $timeout = null, $authStrategy = null): Response
            {
                return ($this->handle)($method, $url, $data);
            }
        };
        $this->app->bind(Client::class, fn () => new Client('ACtest', 'test-token', null, null, $http));
    }

    private function answerChatRequest(string $method, string $url, array $data): Response
    {
        $prefix = 'https://conversations.twilio.com/v1/Services/IStest';
        if (! str_starts_with($url, $prefix)) {
            return new Response(500, json_encode(['message' => 'Unexpected request to '.$url]));
        }
        $path = substr($url, strlen($prefix));
        $this->chatRequests[] = $method.' '.$path.($data === [] ? '' : ' '.json_encode($data));
        $json = fn (int $status, array $body): Response => new Response($status, json_encode($body));
        $page = fn (string $key, array $items): Response => $json(200, [$key => $items, 'meta' => ['page' => 0, 'page_size' => 50, 'first_page_url' => $url, 'previous_page_url' => null, 'url' => $url, 'next_page_url' => null, 'key' => $key]]);
        $notFound = $json(404, ['code' => 20404, 'message' => 'The requested resource was not found', 'status' => 404]);

        if ($method === 'POST' && $path === '/Conversations') {
            $name = $data['UniqueName'];
            $this->conversations[$name] = ['sid' => 'CH'.count($this->conversations), 'participants' => [], 'messages' => []];

            return $json(201, ['sid' => $this->conversations[$name]['sid'], 'unique_name' => $name]);
        }
        if (! preg_match('#^/Conversations/([^/]+)(?:/(Participants|Messages)(?:/([^/]+))?)?$#', $path, $found)) {
            return $notFound;
        }
        $name = null;
        foreach ($this->conversations as $uniqueName => $conversation) {
            if (in_array(rawurldecode($found[1]), [$uniqueName, $conversation['sid']], true)) {
                $name = $uniqueName;
            }
        }
        if ($name === null) {
            return $notFound;
        }
        $kind = $found[2] ?? null;
        $conversation = &$this->conversations[$name];

        if ($kind === null) {
            return $json(200, ['sid' => $conversation['sid'], 'unique_name' => $name]);
        }
        if ($kind === 'Messages') {
            return $page('messages', $conversation['messages']);
        }
        if ($method === 'GET') {
            return $page('participants', $conversation['participants']);
        }
        if (! isset($found[3])) {
            $participant = ['sid' => 'MB'.count($conversation['participants']), 'identity' => $data['Identity'], 'last_read_message_index' => null];
            $conversation['participants'][] = $participant;

            return $json(201, $participant);
        }
        foreach ($conversation['participants'] as &$participant) {
            if ($participant['sid'] === $found[3]) {
                $participant['last_read_message_index'] = (int) $data['LastReadMessageIndex'];

                return $json(200, $participant);
            }
        }

        return $notFound;
    }
}
