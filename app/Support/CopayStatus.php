<?php

namespace App\Support;

/**
 * The co-pay states of an order (`orders.statuse_copay`, named in `statuses_copay`) and the ways a
 * patient can say they will pay (`orders.copay_method`).
 */
class CopayStatus
{
    public const int NO_COPAY = 1;

    public const int DUE = 2;

    public const int PAID_BY_CARD = 3;

    public const int PAID_IN_CASH = 4;

    public const int NOT_PAID = 5;

    public const int PAID_AT_PHARMACY = 6;

    public const int PAID_BY_CHECK = 7;

    /** Nothing more to collect: no co-pay, or paid one way or another. */
    public const array SETTLED = [self::NO_COPAY, self::PAID_BY_CARD, self::PAID_IN_CASH, self::PAID_AT_PHARMACY, self::PAID_BY_CHECK];

    /** Paid, wherever the money went. */
    public const array PAID = [self::PAID_BY_CARD, self::PAID_IN_CASH, self::PAID_AT_PHARMACY, self::PAID_BY_CHECK];

    /** Collected through QuikMedix, which is what a pharmacy's bill is credited with. */
    public const array COLLECTED = [self::PAID_BY_CARD, self::PAID_IN_CASH, self::PAID_BY_CHECK];

    /** Handed to the driver at the door, who has to bring it back. */
    public const array COLLECTED_BY_DRIVER = [self::PAID_IN_CASH, self::PAID_BY_CHECK];

    /** What a patient can choose in the app. */
    public const array METHODS = ['cash', 'check', 'card'];

    public static function isSettled(int|string|null $status): bool
    {
        return in_array((int) $status, self::SETTLED, true);
    }

    public static function isCollected(int|string|null $status): bool
    {
        return in_array((int) $status, self::COLLECTED, true);
    }
}
