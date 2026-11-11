<?php

namespace App\Support;

class OrderStatus
{
    public const int READY_FOR_PICKUP = 1;

    public const int ON_THE_WAY = 3;

    public const int HUB = 7;

    public const int PATIENT_UNAVAILABLE = 8;

    public const int REFUSED = 9;

    /**
     * Statuses a driver may report only with dispatcher approval.
     */
    public const array NEEDS_DISPATCH_APPROVAL = [self::PATIENT_UNAVAILABLE, self::REFUSED];

    public static function label(int|string|null $id, ?string $name = null): string
    {
        return match ((int) $id) {
            self::READY_FOR_PICKUP => 'Ready for pickup',
            self::ON_THE_WAY => 'On the way',
            self::HUB => 'Hub',
            default => trim((string) $name) !== '' ? $name : 'Unknown status',
        };
    }
}
