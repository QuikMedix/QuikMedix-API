<?php

namespace App\Support;

/**
 * What a patient is told about the driver of their order. The apps receive the driver's users
 * row; a patient needs the name, phone, photo and vehicle, not the driver's home address,
 * licence and identity numbers, birth date, card or device.
 */
final class DriverCard
{
    /** Columns a patient has no use for. They stay in the answer, emptied, so the apps still find every field they expect. */
    private const PRIVATE = [
        'device_token', 'payment_card', 'driving_license', 'driving_license_img', 'identification_cards', 'birth_date', 'home_phone',
        'address', 'location', 'apartment', 'city', 'state', 'zip',
        'address2', 'location2', 'apartment2', 'zip2', 'address3', 'location3', 'apartment3', 'zip3',
        'ip', 'last_login', 'quickbook_id', 'auth_chat_id',
    ];

    public static function forPatient(object $driver): object
    {
        $card = clone $driver;
        unset($card->password, $card->remember_token);
        foreach (self::PRIVATE as $column) {
            if (isset($card->{$column})) {
                $card->{$column} = '';
            }
        }

        return $card;
    }
}
