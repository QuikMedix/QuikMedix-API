<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Auth\LoginFormRequest;
use DateInterval;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Laravel\Passport\Passport;

class LoginController extends Controller
{
    public function __invoke(LoginFormRequest $request): JsonResponse
    {
        $guard = Auth::guard('web');

        if (! $guard->once($request->safe()->only(['phone', 'password']))) {
            return response()->json([
                'message' => 'You cannot sign with those credentials',
                'errors' => 'Unauthorised',
            ], 401);
        }

        /** @var \App\User $user */
        $user = $guard->user();
        $previousExpiration = Passport::personalAccessTokensExpireIn();

        // Set the signed JWT lifetime before issuance and restore it for later requests.
        try {
            Passport::personalAccessTokensExpireIn(new DateInterval($request->boolean('remember_me') ? 'P1M' : 'P7D'));
            $token = $user->createToken(config('app.name'));
        } finally {
            Passport::personalAccessTokensExpireIn($previousExpiration);
        }

        $user->os = $request->integer('os');
        $user->save();

        return response()->json([
            'token_type' => 'Bearer',
            'token' => $token->accessToken,
            'expires_at' => $token->getToken()->expires_at->toDateTimeString(),
        ]);
    }
}
