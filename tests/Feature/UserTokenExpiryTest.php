<?php

namespace Tests\Feature;

use App\User;
use Illuminate\Support\Carbon;
use Laravel\Passport\AccessToken;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Passport;
use Laravel\Passport\TransientToken;
use Tests\Support\CreatesAuthenticationSchema;
use Tests\TestCase;

class UserTokenExpiryTest extends TestCase
{
    use CreatesAuthenticationSchema;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createAuthenticationSchema();
    }

    public function test_extends_the_stored_expiry_of_the_current_access_token(): void
    {
        Carbon::setTestNow('2026-09-22 12:00:00');
        $user = User::factory()->create();
        app(ClientRepository::class)->createPersonalAccessGrantClient('Test client', 'users');
        $tokenId = $user->createToken('test')->getToken()->getKey();
        Passport::token()->newQuery()->whereKey($tokenId)->update(['expires_at' => now()->addDay()]);

        $user->withAccessToken(new AccessToken(['oauth_access_token_id' => $tokenId]));
        $user->extendTokenExpiry();

        $this->assertEquals(now()->addDays(7), Passport::token()->newQuery()->find($tokenId)->expires_at);
    }

    public function test_ignores_cookie_based_transient_tokens(): void
    {
        $user = User::factory()->create();

        $user->withAccessToken(new TransientToken);
        $user->extendTokenExpiry();

        $this->assertDatabaseCount('oauth_access_tokens', 0);
    }
}
