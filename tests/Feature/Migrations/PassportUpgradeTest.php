<?php

namespace Tests\Feature\Migrations;

use App\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Once;
use Laravel\Passport\ClientRepository;
use Tests\Support\CreatesAuthenticationSchema;
use Tests\TestCase;

class PassportUpgradeTest extends TestCase
{
    use CreatesAuthenticationSchema;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createAuthenticationSchema();
    }

    private function useLegacyClients(): void
    {
        Schema::drop('oauth_clients');
        Schema::create('oauth_clients', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('name');
            $table->string('secret', 100)->nullable();
            $table->string('provider')->nullable();
            $table->text('redirect');
            $table->boolean('personal_access_client');
            $table->boolean('password_client');
            $table->boolean('revoked');
            $table->timestamps();
        });
    }

    private function migration(): object
    {
        return require database_path('migrations/2026_09_22_042529_add_grant_types_to_legacy_oauth_clients_table.php');
    }

    private function createLegacyClient(array $overrides = []): void
    {
        DB::table('oauth_clients')->insert(array_merge([
            'id' => 1, 'user_id' => null, 'name' => 'Legacy personal access client',
            'secret' => 'test-only-client-secret', 'provider' => 'users', 'redirect' => 'http://localhost',
            'personal_access_client' => true, 'password_client' => false, 'revoked' => false,
            'created_at' => '2020-01-01 00:00:00', 'updated_at' => '2020-01-01 00:00:00',
        ], $overrides));
    }

    public function test_existing_tokens_remain_usable_when_user_and_client_ids_match(): void
    {
        $this->useLegacyClients();
        $this->createLegacyClient();
        $user = User::factory()->create(['id' => 1]);
        $token = $user->createToken('Previously issued token');

        $this->migration()->up();
        // A deployment restarts workers; discard the client cached while minting the old token.
        Once::flush();

        $this->postJson('/api/logout', [], ['Authorization' => 'Bearer '.$token->accessToken])
            ->assertOk();
        $this->assertDatabaseHas('oauth_access_tokens', ['id' => $token->accessTokenId, 'revoked' => true]);
        $this->assertDatabaseHas('oauth_clients', [
            'id' => 1, 'secret' => 'test-only-client-secret', 'updated_at' => '2020-01-01 00:00:00',
        ]);
    }

    public function test_upgraded_legacy_schema_accepts_new_clients_and_rollback_preserves_their_flags(): void
    {
        $this->useLegacyClients();
        $this->createLegacyClient();
        $this->migration()->up();
        $this->migration()->up();

        $client = app(ClientRepository::class)->createPersonalAccessGrantClient('New client', 'users');
        $user = User::factory()->create();
        $token = $user->createToken('New token');

        $this->assertDatabaseHas('oauth_access_tokens', ['id' => $token->accessTokenId, 'client_id' => $client->id]);
        $this->migration()->down();
        $this->assertDatabaseHas('oauth_clients', ['id' => $client->id, 'personal_access_client' => true, 'password_client' => false]);
    }

    public function test_migration_retains_distinct_password_and_client_credentials_grants(): void
    {
        $this->useLegacyClients();
        $this->createLegacyClient(['id' => 2, 'personal_access_client' => false, 'password_client' => true]);
        $this->createLegacyClient(['id' => 3, 'personal_access_client' => false, 'redirect' => '']);

        $this->migration()->up();

        $clients = app(ClientRepository::class);
        $this->assertTrue($clients->find(2)->hasGrantType('password'));
        $this->assertFalse($clients->find(2)->hasGrantType('client_credentials'));
        $this->assertTrue($clients->find(3)->hasGrantType('client_credentials'));
    }

    public function test_hash_command_preserves_existing_secrets_and_is_repeatable(): void
    {
        $this->useLegacyClients();
        $this->createLegacyClient();

        $this->artisan('passport:hash', ['--force' => true])->assertSuccessful();
        $hashed = DB::table('oauth_clients')->value('secret');
        $this->assertTrue(Hash::check('test-only-client-secret', $hashed));
        $this->artisan('passport:hash', ['--force' => true])->assertSuccessful();
        $this->assertSame($hashed, DB::table('oauth_clients')->value('secret'));
    }

    public function test_migration_and_rollback_leave_a_fresh_passport_schema_usable(): void
    {
        Schema::drop('oauth_clients');
        Schema::create('oauth_clients', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->nullableMorphs('owner');
            $table->string('name');
            $table->string('secret')->nullable();
            $table->string('provider')->nullable();
            $table->text('redirect_uris');
            $table->text('grant_types');
            $table->boolean('revoked');
            $table->timestamps();
        });
        $this->migration()->up();
        $this->migration()->down();

        $client = app(ClientRepository::class)->createPersonalAccessGrantClient('Fresh client', 'users');
        $user = User::factory()->create();
        $token = $user->createToken('Fresh token');

        $this->assertDatabaseHas('oauth_access_tokens', ['id' => $token->accessTokenId, 'client_id' => $client->id]);
    }

    public function test_interrupted_backfill_can_resume_without_overwriting_existing_grants(): void
    {
        $this->useLegacyClients();
        $this->createLegacyClient();
        $this->createLegacyClient(['id' => 2, 'personal_access_client' => false, 'password_client' => true]);
        $this->migration()->up();
        DB::table('oauth_clients')->where('id', 1)->update(['grant_types' => '["personal_access"]']);
        DB::table('oauth_clients')->where('id', 2)->update(['grant_types' => null]);

        $this->migration()->up();

        $this->assertDatabaseHas('oauth_clients', ['id' => 1, 'grant_types' => '["personal_access"]']);
        $this->assertTrue(app(ClientRepository::class)->find(2)->hasGrantType('password'));
    }
}
