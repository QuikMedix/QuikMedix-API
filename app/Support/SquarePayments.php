<?php

namespace App\Support;

use Square\Models\CreatePaymentRequest;
use Square\Models\Money as SquareMoney;
use Square\Models\RefundPaymentRequest;
use Square\SquareClient;

/**
 * The charges and refunds the application makes at Square.
 */
final class SquarePayments
{
    public function __construct(private SquareClient $client) {}

    /**
     * Charges a card (a saved card's id or a one-time token) in US dollars.
     */
    public function charge(string $source, ?string $customerId, float|int|string $dollars, string $note): SquareCharge
    {
        $body = new CreatePaymentRequest($source, self::idempotencyKey());
        $body->setAmountMoney(self::usd($dollars));
        $body->setAutocomplete(true);
        $body->setCustomerId($customerId);
        $body->setLocationId(config('app.SQUARE_LOCATION_ID'));
        $body->setNote($note);
        $response = $this->client->getPaymentsApi()->createPayment($body);
        if (! $response->isSuccess()) {
            return new SquareCharge(errors: json_encode($response->getErrors()));
        }
        $payment = $response->getResult()->getPayment();

        return new SquareCharge($payment->getId(), $payment->getStatus());
    }

    /**
     * Gives a payment back in full or in part.
     *
     * @return string|null Square's errors as JSON, or null when the refund went through
     */
    public function refund(string $paymentId, float|int|string $dollars): ?string
    {
        $body = new RefundPaymentRequest(self::idempotencyKey(), self::usd($dollars));
        $body->setPaymentId($paymentId);
        $response = $this->client->getRefundsApi()->refundPayment($body);

        return $response->isSuccess() ? null : json_encode($response->getErrors());
    }

    /** A payment's current status at Square, or null when Square does not answer for it. */
    public function status(string $paymentId): ?string
    {
        $response = $this->client->getPaymentsApi()->getPayment($paymentId);

        return $response->isSuccess() ? $response->getResult()->getPayment()?->getStatus() : null;
    }

    /** Square refuses a repeated key, so each request gets its own. */
    public static function idempotencyKey(): string
    {
        return uniqid('', true).rand(0, 100);
    }

    private static function usd(float|int|string $dollars): SquareMoney
    {
        $money = new SquareMoney;
        $money->setAmount(Money::cents($dollars));
        $money->setCurrency('USD');

        return $money;
    }
}
