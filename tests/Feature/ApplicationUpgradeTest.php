<?php

namespace Tests\Feature;

use Tests\TestCase;

class ApplicationUpgradeTest extends TestCase
{
    public function test_login_page_renders(): void
    {
        $this->get('/login')->assertOk()->assertViewIs('auth.login');
    }

    public function test_password_reset_page_renders(): void
    {
        $this->get('/password/reset')->assertOk()->assertViewIs('auth.passwords.email');
    }

    public function test_unauthenticated_api_requests_return_json_without_an_accept_header(): void
    {
        $this->get('/api/profile')->assertUnauthorized()->assertJsonPath('message', 'Unauthenticated.');
    }

    public function test_unauthenticated_chat_requests_redirect_to_login(): void
    {
        $this->get('/chat')->assertRedirectToRoute('login');
    }

    public function test_unknown_api_routes_return_json_404(): void
    {
        $this->get('/api/not-a-real-route')->assertNotFound()->assertHeader('Content-Type', 'application/json');
    }

    public function test_browser_post_requires_csrf_protection(): void
    {
        $this->app['env'] = 'local';

        $this->post('/login', [])->assertStatus(419);
    }

    public function test_api_cors_preflight_preserves_the_existing_policy(): void
    {
        $this->options('/api/login', [], [
            'Origin' => 'https://client.example.org',
            'Access-Control-Request-Method' => 'POST',
        ])->assertNoContent()->assertHeader('Access-Control-Allow-Origin', '*');
    }

    public function test_legacy_controller_actions_with_numeric_suffixes_still_resolve(): void
    {
        $this->get('/billing-old/1')->assertRedirectToRoute('login');
        $this->postJson('/api/orders/drop_off_qr2')->assertUnauthorized();
    }
}
