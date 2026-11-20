<?php

namespace Tests\Support;

use Twilio\Http\Client as HttpClient;
use Twilio\Http\Response;
use Twilio\Rest\Client;

/**
 * Puts a Twilio client that cannot reach the network where the application builds its own, so no
 * test sends a text message. The real SDK still builds each request; the stand-in records it.
 */
trait FakesSms
{
    /** @var list<array{to: string, from: string, body: string}> the texts Twilio was asked to send */
    protected array $texts = [];

    /** @var list<string> the address of each request */
    protected array $smsRequests = [];

    /**
     * @param  array{code: int, message: string}|null  $refusal  what Twilio answers instead of accepting
     * @param  string|null  $from  TWILIO_FROM_PHONE as the deployment writes it
     */
    protected function fakeSms(?array $refusal = null, ?string $from = '8888988284'): void
    {
        config(['app.twilio_sid' => 'ACtest', 'app.twilio_auth_token' => 'test-token', 'app.twilio_from_phone' => $from]);
        $this->texts = [];
        $this->smsRequests = [];
        $record = function (string $method, string $url, array $data): void {
            $this->smsRequests[] = $method.' '.$url;
            $this->texts[] = ['to' => $data['To'] ?? '', 'from' => $data['From'] ?? '', 'body' => $data['Body'] ?? ''];
        };
        $http = new class($record, $refusal) implements HttpClient
        {
            public function __construct(private \Closure $record, private ?array $refusal) {}

            public function request(string $method, string $url, array $params = [], array $data = [], array $headers = [], ?string $user = null, ?string $password = null, ?int $timeout = null, $authStrategy = null): Response
            {
                ($this->record)($method, $url, $data);

                return $this->refusal === null
                    ? new Response(201, json_encode(['sid' => 'SM123', 'status' => 'queued']))
                    : new Response(400, json_encode($this->refusal + ['status' => 400]));
            }
        };
        $this->app->bind(Client::class, fn () => new Client('ACtest', 'test-token', null, null, $http));
    }
}
