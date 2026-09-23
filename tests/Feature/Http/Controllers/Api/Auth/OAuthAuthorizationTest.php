<?php

namespace Tests\Feature\Http\Controllers\Api\Auth;

use App\User;
use Laravel\Passport\ClientRepository;
use Tests\Support\CreatesAuthenticationSchema;
use Tests\TestCase;

class OAuthAuthorizationTest extends TestCase
{
    use CreatesAuthenticationSchema;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createAuthenticationSchema();
    }

    public function test_oauth_consent_renders_escaped_client_name_and_approval_issues_a_code(): void
    {
        $user = User::factory()->create();
        $client = app(ClientRepository::class)->createAuthorizationCodeGrantClient('<script>alert(1)</script>', ['http://localhost/callback']);
        $this->actingAs($user);
        $parameters = ['client_id' => $client->id, 'redirect_uri' => 'http://localhost/callback', 'response_type' => 'code', 'state' => 'test-state'];

        $this->get('/oauth/authorize?'.http_build_query($parameters))
            ->assertOk()->assertViewIs('auth.oauth.authorize')
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)
            ->assertDontSee('<script>alert(1)</script>', false);
        $response = $this->post('/oauth/authorize', [
            'client_id' => $client->id, 'state' => 'test-state', 'auth_token' => session('authToken'),
        ])->assertRedirect();

        parse_str(parse_url($response->headers->get('Location'), PHP_URL_QUERY), $query);
        $this->assertSame('test-state', $query['state']);
        $this->assertNotEmpty($query['code']);
        $this->assertDatabaseCount('oauth_auth_codes', 1);
    }

    public function test_guests_must_login_before_oauth_consent(): void
    {
        $client = app(ClientRepository::class)->createAuthorizationCodeGrantClient('Fixture client', ['http://localhost/callback']);
        $parameters = ['client_id' => $client->id, 'redirect_uri' => 'http://localhost/callback', 'response_type' => 'code'];

        $this->get('/oauth/authorize?'.http_build_query($parameters))->assertRedirectToRoute('login');
    }

    public function test_existing_oauth_management_endpoint_is_available_to_authenticated_users(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->getJson('/oauth/clients')->assertOk()->assertExactJson([]);
    }
}
