<?php

namespace App\Support;

use Illuminate\Support\Facades\Redis;
use RuntimeException;

/**
 * Shared pieces of the HERE routing integration used for driver ETAs.
 */
class HereMaps
{
    private const TOKEN_CACHE_KEY = 'here_access_token';

    private const TOKEN_URL = 'https://account.api.here.com/oauth2/token';

    /**
     * Rounds a stored "lat,lng" location to five decimals, the precision ETA requests and
     * the "has the driver moved" comparison use. Anything that is not a non-empty string becomes ''.
     */
    public static function roundLocation(mixed $location): string
    {
        if (! is_string($location) || $location === '') {
            return '';
        }
        $parts = explode(',', $location);
        if (count($parts) > 1) {
            $parts[0] = round(floatval($parts[0]), 5);
            $parts[1] = round(floatval($parts[1]), 5);
        }

        return implode(',', $parts);
    }

    /**
     * The cached OAuth token for routing requests, refreshed when it has expired.
     */
    public static function accessToken(): string
    {
        $accessToken = Redis::get(self::TOKEN_CACHE_KEY);

        return empty($accessToken) ? self::refreshAccessToken() : $accessToken;
    }

    /**
     * Requests a new token with the configured HERE credentials and caches it until it expires.
     *
     * @throws RuntimeException when HERE does not return a token
     */
    public static function refreshAccessToken(): string
    {
        $nonce = bin2hex(random_bytes(4));
        $timestamp = time();
        $string = 'POST&'.urlencode(self::TOKEN_URL).'&'.urlencode('grant_type=client_credentials&'.'oauth_consumer_key='.config('app.hereAccessId').'&oauth_nonce='.$nonce.'&oauth_signature_method=HMAC-SHA256&oauth_timestamp='.$timestamp.'&oauth_version=1.0');
        $signature = urlencode(base64_encode(hash_hmac('sha256', $string, config('app.hereAccessSecret').'&', true)));
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => self::TOKEN_URL,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => 'grant_type=client_credentials',
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/x-www-form-urlencoded',
                'Authorization: OAuth oauth_consumer_key="'.config('app.hereAccessId').'",oauth_nonce="'.$nonce.'",oauth_signature_method="HMAC-SHA256",oauth_timestamp="'.$timestamp.'",oauth_version="1.0",oauth_signature="'.$signature.'"',
            ],
        ]);
        $response = json_decode(curl_exec($curl));
        if (isset($response->access_token) && isset($response->expires_in)) {
            Redis::setex(self::TOKEN_CACHE_KEY, intval($response->expires_in), $response->access_token);

            return $response->access_token;
        }

        throw new RuntimeException('Could not refresh the HERE Maps access token.');
    }
}
