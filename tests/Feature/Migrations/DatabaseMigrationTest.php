<?php

namespace Tests\Feature\Migrations;

use Laravel\Passport\ClientRepository;
use Tests\TestCase;

class DatabaseMigrationTest extends TestCase
{
    public function test_repository_migrations_support_a_fresh_test_database(): void
    {
        $this->artisan('migrate', ['--force' => true])->assertSuccessful();

        $client = app(ClientRepository::class)->createPersonalAccessGrantClient('Fresh installation', 'users');

        $this->assertDatabaseHas('oauth_clients', ['id' => $client->id, 'name' => 'Fresh installation']);
    }
}
