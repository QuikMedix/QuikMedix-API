<?php

namespace Tests\Support;

use Mockery;
use Square\Apis\CardsApi;
use Square\Apis\CustomersApi;
use Square\Apis\PaymentsApi;
use Square\Apis\RefundsApi;
use Square\Http\ApiResponse;
use Square\Models\Card;
use Square\Models\CreateCardResponse;
use Square\Models\CreateCustomerResponse;
use Square\Models\CreatePaymentResponse;
use Square\Models\Customer;
use Square\Models\GetPaymentResponse;
use Square\Models\Payment;
use Square\SquareClient;

/**
 * Puts a stand-in where the application builds its Square client, so no test can reach Square.
 * It records what was asked and answers from a script.
 */
trait FakesSquare
{
    /** @var array<string, list<mixed>> what was asked of Square, by call name */
    protected array $square = [];

    /**
     * Each entry is what that call answers: a string or true for success (a payment status, or
     * the new id), or a list of errors for a refusal. Calls left out succeed.
     *
     * @param  array<string, string|true|list<array<string, string>>>  $script
     */
    protected function fakeSquare(array $script = []): void
    {
        $script += ['createCustomer' => 'cust_new', 'createPayment' => 'COMPLETED', 'createCard' => 'card_new', 'refundPayment' => true, 'getPayment' => 'COMPLETED'];
        $this->square = ['createCustomer' => [], 'createPayment' => [], 'createCard' => [], 'refundPayment' => [], 'getPayment' => []];

        $answer = function (string $call, mixed $asked) use ($script) {
            $this->square[$call][] = $asked;
            $scripted = $script[$call];
            $response = Mockery::mock(ApiResponse::class);
            $response->shouldReceive('isSuccess')->andReturn(! is_array($scripted));
            $response->shouldReceive('getErrors')->andReturn(is_array($scripted) ? $scripted : []);
            if (! is_array($scripted)) {
                $response->shouldReceive('getResult')->andReturn(match ($call) {
                    'createCustomer' => tap(new CreateCustomerResponse, fn ($r) => $r->setCustomer(tap(new Customer, fn ($c) => $c->setId($scripted)))),
                    'createPayment', 'getPayment' => tap($call === 'getPayment' ? new GetPaymentResponse : new CreatePaymentResponse, fn ($r) => $r->setPayment(tap(new Payment, function ($p) use ($scripted): void {
                        $p->setId('pay_123');
                        $p->setStatus($scripted);
                    }))),
                    'createCard' => tap(new CreateCardResponse, fn ($r) => $r->setCard(tap(new Card, function ($c) use ($scripted): void {
                        $c->setId($scripted);
                        $c->setLast4('4242');
                    }))),
                    default => null,
                });
            }

            return $response;
        };

        $client = Mockery::mock(SquareClient::class);
        foreach ([[CustomersApi::class, 'getCustomersApi', ['createCustomer']], [PaymentsApi::class, 'getPaymentsApi', ['createPayment', 'getPayment']], [CardsApi::class, 'getCardsApi', ['createCard']], [RefundsApi::class, 'getRefundsApi', ['refundPayment']]] as [$class, $getter, $calls]) {
            $api = Mockery::mock($class);
            foreach ($calls as $call) {
                $api->shouldReceive($call)->andReturnUsing(fn ($asked) => $answer($call, $asked));
            }
            $client->shouldReceive($getter)->andReturn($api);
        }
        $this->app->bind(SquareClient::class, fn () => $client);
    }
}
