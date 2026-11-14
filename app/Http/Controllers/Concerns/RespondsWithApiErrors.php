<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\JsonResponse;

/**
 * The error bodies the mobile apps already expect from the JSON API.
 */
trait RespondsWithApiErrors
{
    protected function forbidden(string $message = 'You cannot open this page'): JsonResponse
    {
        return response()->json(['message' => $message, 'errors' => 'Forbidden'], 403);
    }

    protected function badRequest(string $message = 'Something is wrong with this field!'): JsonResponse
    {
        return response()->json(['message' => $message, 'errors' => 'Bad Request'], 400);
    }

    protected function notFound(string $message): JsonResponse
    {
        return response()->json(['message' => $message, 'errors' => 'Not Found'], 404);
    }
}
