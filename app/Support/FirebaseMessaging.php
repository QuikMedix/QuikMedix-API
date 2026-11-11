<?php

namespace App\Support;

use Firebase\JWT\JWT;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Sends push notifications through the Firebase Cloud Messaging HTTP v1 API.
 *
 * Google shut down the legacy `fcm/send` API (server key) in 2024. HTTP v1
 * authenticates with a service account from the Firebase project instead:
 * Firebase console → Project settings → Service accounts → Generate new private key.
 */
class FirebaseMessaging
{
    public const RESULT_SENT = 'sent';
    public const RESULT_INVALID_TOKEN = 'invalid_token';
    public const RESULT_FAILED = 'failed';

    private const SCOPE = 'https://www.googleapis.com/auth/firebase.messaging';

    public static function isConfigured(): bool
    {
        return self::credentials() !== null;
    }

    /**
     * Confirms the service account can get a token from Google.
     *
     * @return string|null an error message, or null when push is ready
     */
    public static function verifyCredentials(): ?string
    {
        $credentials = self::credentials();
        if ($credentials === null) {
            return 'FCM_CREDENTIALS is missing or is not a valid service account JSON (file path or raw JSON).';
        }
        try {
            Cache::forget('fcm_v1_access_token:'.$credentials['client_email']);
            self::accessToken($credentials);
        } catch (\Throwable $e) {
            return 'Google rejected the service account: '.$e->getMessage();
        }

        return null;
    }

    /**
     * @return string one of the RESULT_* constants
     */
    public static function send(string $deviceToken, string $title, string $body): string
    {
        $credentials = self::credentials();
        if ($credentials === null) {
            return self::RESULT_FAILED;
        }

        try {
            $response = Http::withToken(self::accessToken($credentials))
                ->timeout(10)
                ->post("https://fcm.googleapis.com/v1/projects/{$credentials['project_id']}/messages:send", [
                    'message' => [
                        'token' => $deviceToken,
                        'notification' => ['title' => $title, 'body' => $body],
                        'android' => ['notification' => [
                            'icon' => config('services.fcm.notification_icon'),
                            'sound' => 'default',
                        ]],
                        'apns' => ['payload' => ['aps' => ['sound' => 'default']]],
                    ],
                ]);
        } catch (\Throwable $e) {
            Log::warning('FCM send failed', ['error' => $e->getMessage()]);

            return self::RESULT_FAILED;
        }

        if ($response->successful()) {
            return self::RESULT_SENT;
        }

        // The app was uninstalled or the token rotated; the caller should forget it.
        $errorCode = $response->json('error.details.0.errorCode') ?? $response->json('error.status');
        if ($response->status() === 404 || $errorCode === 'UNREGISTERED') {
            return self::RESULT_INVALID_TOKEN;
        }

        Log::warning('FCM send rejected', ['status' => $response->status(), 'body' => $response->body()]);

        return self::RESULT_FAILED;
    }

    /**
     * Service account JSON, from FCM_CREDENTIALS (a file path or the raw JSON).
     *
     * @return array{project_id: string, client_email: string, private_key: string, token_uri: string}|null
     */
    private static function credentials(): ?array
    {
        $value = config('services.fcm.credentials');
        if (empty($value)) {
            return null;
        }
        $json = str_starts_with(ltrim($value), '{') ? $value : (is_readable($value) ? file_get_contents($value) : null);
        $data = $json ? json_decode($json, true) : null;
        if (! is_array($data) || empty($data['client_email']) || empty($data['private_key'])) {
            return null;
        }
        $data['project_id'] = config('services.fcm.project_id') ?: ($data['project_id'] ?? null);
        $data['token_uri'] = $data['token_uri'] ?? 'https://oauth2.googleapis.com/token';

        return empty($data['project_id']) ? null : $data;
    }

    /** OAuth access tokens last an hour; reuse one for 50 minutes. */
    private static function accessToken(array $credentials): string
    {
        return Cache::remember('fcm_v1_access_token:'.$credentials['client_email'], 3000, function () use ($credentials) {
            $now = time();
            $assertion = JWT::encode([
                'iss' => $credentials['client_email'],
                'scope' => self::SCOPE,
                'aud' => $credentials['token_uri'],
                'iat' => $now,
                'exp' => $now + 3600,
            ], $credentials['private_key'], 'RS256');

            return Http::asForm()->timeout(10)->post($credentials['token_uri'], [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $assertion,
            ])->throw()->json('access_token');
        });
    }
}
