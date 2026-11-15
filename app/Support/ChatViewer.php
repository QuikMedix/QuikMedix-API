<?php

namespace App\Support;

/**
 * The chat page runs in the mobile apps' web view and calls the API without a bearer token.
 * The page is rendered for a signed-in person, so it carries a signature naming that person;
 * the chat endpoints accept the signature in place of a token.
 */
class ChatViewer
{
    private const LIFETIME_SECONDS = 12 * 60 * 60;

    public static function sign(int|string $userId): string
    {
        $expires = now()->getTimestamp() + self::LIFETIME_SECONDS;

        return $expires.'.'.self::mac((string) $userId, $expires);
    }

    public static function verify(mixed $userId, mixed $signature): bool
    {
        if (! is_scalar($userId) || ! is_string($signature) || ! str_contains($signature, '.')) {
            return false;
        }
        [$expires, $mac] = explode('.', $signature, 2);

        return ctype_digit($expires) && (int) $expires >= now()->getTimestamp() && hash_equals(self::mac((string) $userId, (int) $expires), $mac);
    }

    private static function mac(string $userId, int $expires): string
    {
        return hash_hmac('sha256', 'chat-viewer|'.$userId.'|'.$expires, (string) config('app.key'));
    }
}
