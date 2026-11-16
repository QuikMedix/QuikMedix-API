<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Several endpoints answer with whole rows of the users table, for example the driver of an
 * order. Those rows carry the password hash and the remember-me token, which no client should
 * ever receive. This takes both out of every JSON answer, whatever built it.
 */
class RemoveSecretsFromJson
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $content = $response->getContent();

        if (! is_string($content) || $content === '' || ($content[0] !== '{' && $content[0] !== '[')
            || (! str_contains($content, '"password"') && ! str_contains($content, '"remember_token"'))) {
            return $response;
        }

        $data = json_decode($content);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return $response;
        }
        $this->clean($data);

        return $response instanceof JsonResponse
            ? $response->setData($data)
            : $response->setContent((string) json_encode($data));
    }

    /**
     * Removes remember-me tokens and password hashes at any depth. A "password" entry that is
     * not a hash, such as a validation message about the password field, is left alone.
     */
    private function clean(mixed &$value): void
    {
        if (is_array($value)) {
            foreach ($value as &$item) {
                $this->clean($item);
            }

            return;
        }
        if (! is_object($value)) {
            return;
        }
        unset($value->remember_token);
        if (isset($value->password) && is_string($value->password) && preg_match('/^\$(2[aby]|argon2id?)\$/', $value->password)) {
            unset($value->password);
        }
        foreach (get_object_vars($value) as $key => $item) {
            $this->clean($value->{$key});
        }
    }
}
