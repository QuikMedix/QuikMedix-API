<?php

namespace Tests\Feature\Http\Controllers\Api\Auth;

use App\User;
use DateInterval;
use DateTimeImmutable;
use DateTimeZone;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Passport;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\CreatesAuthenticationSchema;
use Tests\TestCase;

class LoginControllerTest extends TestCase
{
    use CreatesAuthenticationSchema;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createAuthenticationSchema();
    }

    public function test_missing_credentials_return_422(): void
    {
        $this->postJson('/api/login', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['phone', 'password']);

        $this->assertDatabaseCount('oauth_access_tokens', 0);
    }

    public function test_non_scalar_phone_returns_422(): void
    {
        $this->postJson('/api/login', ['phone' => ['invalid'], 'password' => 'password'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('phone');
    }

    public function test_invalid_credentials_return_401_without_issuing_a_token(): void
    {
        User::factory()->create(['phone' => '5550000001']);

        $this->postJson('/api/login', ['phone' => '5550000001', 'password' => 'incorrect'])
            ->assertUnauthorized()
            ->assertJsonPath('errors', 'Unauthorised');

        $this->assertDatabaseCount('oauth_access_tokens', 0);
        $this->assertGuest('web');
    }

    public function test_repeated_failed_logins_are_rate_limited(): void
    {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->postJson('/api/login', ['phone' => '5550000002', 'password' => 'incorrect'])
                ->assertUnauthorized();
        }

        $this->postJson('/api/login', ['phone' => '5550000002', 'password' => 'incorrect'])
            ->assertTooManyRequests();
    }

    #[DataProvider('tokenLifetimes')]
    public function test_login_issues_a_token_with_matching_signed_and_stored_expiry(bool $remember, string $interval): void
    {
        $user = User::factory()->create(['phone' => '5550000003']);
        $client = app(ClientRepository::class)->createPersonalAccessGrantClient('Test client', 'users');
        $previousExpiration = Passport::personalAccessTokensExpireIn();

        $response = $this->postJson('/api/login', [
            'phone' => $user->phone,
            'password' => 'password',
            'remember_me' => $remember,
            'os' => 2,
        ])->assertOk()->assertJsonStructure(['token_type', 'token', 'expires_at']);

        $claims = JWT::decode($response->json('token'), new Key(config('passport.public_key'), 'RS256'));
        $expectedExpiry = (new DateTimeImmutable('@'.$claims->iat))
            ->setTimezone(new DateTimeZone(config('app.timezone')))
            ->add(new DateInterval($interval))->getTimestamp();
        $this->assertSame($expectedExpiry, (int) $claims->exp);
        $this->assertSame((int) $claims->exp, Carbon::parse($response->json('expires_at'))->timestamp);
        $this->assertSame('Bearer', $response->json('token_type'));
        $this->assertSame($previousExpiration, Passport::personalAccessTokensExpireIn());
        $this->assertDatabaseHas('users', ['id' => $user->id, 'os' => 2]);
        $this->assertDatabaseHas('oauth_access_tokens', [
            'id' => $claims->jti, 'user_id' => $user->id, 'client_id' => $client->id, 'revoked' => false,
        ]);
        $this->assertSame((int) $claims->exp, Carbon::parse(DB::table('oauth_access_tokens')->value('expires_at'))->timestamp);
        $response->assertCookieMissing(config('session.cookie'));
    }

    public static function tokenLifetimes(): array
    {
        return ['normal login' => [false, 'P7D'], 'remembered login' => [true, 'P1M']];
    }

    public function test_failed_token_issuance_restores_the_default_lifetime(): void
    {
        $user = User::factory()->create(['phone' => '5550000005']);
        $previousExpiration = Passport::personalAccessTokensExpireIn();

        // There is deliberately no personal access client in this isolated database.
        $this->postJson('/api/login', [
            'phone' => $user->phone, 'password' => 'password', 'remember_me' => true,
        ])->assertStatus(500);

        $this->assertSame($previousExpiration, Passport::personalAccessTokensExpireIn());
        $this->assertDatabaseCount('oauth_access_tokens', 0);
    }

    public function test_logout_revokes_the_bearer_token_and_prevents_reuse(): void
    {
        $user = User::factory()->create(['phone' => '5550000004']);
        app(ClientRepository::class)->createPersonalAccessGrantClient('Test client', 'users');
        $login = $this->postJson('/api/login', ['phone' => $user->phone, 'password' => 'password'])->assertOk();
        $headers = ['Authorization' => 'Bearer '.$login->json('token')];
        $this->app['auth']->forgetGuards();

        $this->postJson('/api/logout', [], $headers)
            ->assertOk()->assertJsonPath('message', 'You are successfully logged out');

        $this->assertDatabaseHas('oauth_access_tokens', ['user_id' => $user->id, 'revoked' => true]);
        $this->app['auth']->forgetGuards();
        $this->postJson('/api/logout', [], $headers)->assertUnauthorized();
    }
}
