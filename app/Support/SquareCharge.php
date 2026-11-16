<?php

namespace App\Support;

/**
 * What Square answered to one charge.
 */
final readonly class SquareCharge
{
    /**
     * @param  string|null  $errors  Square's errors as JSON when it refused the charge
     */
    public function __construct(
        public ?string $paymentId = null,
        public ?string $status = null,
        public ?string $errors = null,
    ) {}

    /** The money has moved. */
    public function completed(): bool
    {
        return in_array($this->status, ['COMPLETED', 'APPROVED'], true);
    }

    /** The money has moved or is on its way, as a bank payment is for some days. */
    public function completedOrPending(): bool
    {
        return $this->completed() || $this->status === 'PENDING';
    }

    /** What to tell the person when the charge did not go through. */
    public function failure(): string
    {
        return $this->errors ?? 'Payment not completed!';
    }
}
