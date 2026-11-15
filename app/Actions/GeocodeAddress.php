<?php

namespace App\Actions;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class GeocodeAddress
{
    private const URL = 'https://maps.googleapis.com/maps/api/geocode/json';

    /**
     * Resolves an address to "lat,lng", reporting failures as a validation error on $field.
     */
    public function handle(string $address, string $field = 'address'): string
    {
        return $this->lookup($address, $field)['location'];
    }

    /**
     * Google's answer as it is, for the older pages that read results[0] themselves. When Google
     * cannot be reached or answers with something unreadable, the answer has no results.
     */
    public function response(?string $address): object
    {
        try {
            $answer = json_decode(Http::timeout(10)->get(self::URL, ['address' => $address, 'key' => config('app.googlemaps_apikey')])->body());
        } catch (ConnectionException) {
            $answer = null;
        }

        return is_object($answer) ? $answer : (object) ['status' => 'UNAVAILABLE', 'results' => []];
    }

    /**
     * Resolves an address to its "lat,lng" location and Google's formatted address.
     *
     * @return array{location: string, formatted_address: string}
     */
    public function lookup(?string $address, string $field = 'address'): array
    {
        $fail = fn (string $message) => throw ValidationException::withMessages([$field => $message]);

        if (trim((string) $address) === '') {
            $fail('Enter an address.');
        }

        if (!config('app.googlemaps_apikey')) {
            $fail('Address lookup is not configured. Ask an administrator to enable Google Maps.');
        }

        try {
            $response = Http::timeout(10)->get(self::URL, [
                'address' => $address,
                'key' => config('app.googlemaps_apikey'),
            ]);
        } catch (ConnectionException) {
            $fail('Address lookup is temporarily unavailable. Please try again.');
        }

        if ($response->json('status') === 'REQUEST_DENIED') {
            $fail('Address lookup is not authorized. Ask an administrator to enable the Geocoding API and check the Google Maps API key settings.');
        }

        $latitude = $response->json('results.0.geometry.location.lat');
        $longitude = $response->json('results.0.geometry.location.lng');

        if (!$response->successful() || $response->json('status') !== 'OK'
            || !is_numeric($latitude) || !is_numeric($longitude)) {
            $fail('We could not find this address. Check the street, city and ZIP code, or pick a suggestion from the list.');
        }

        return [
            'location' => $latitude.','.$longitude,
            'formatted_address' => (string) $response->json('results.0.formatted_address', $address),
        ];
    }
}
