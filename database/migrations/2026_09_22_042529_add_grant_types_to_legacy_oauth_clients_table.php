<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $schema = Schema::connection($this->getConnection());

        // Fresh installations already use Passport 13's client schema.
        if (! $schema->hasColumn('oauth_clients', 'personal_access_client')) {
            return;
        }

        // Tables created before Passport 9 lack the provider column Passport 13 always writes.
        if (! $schema->hasColumn('oauth_clients', 'provider')) {
            $schema->table('oauth_clients', function (Blueprint $table): void {
                $table->string('provider')->nullable()->after('secret');
            });
        }

        if (! $schema->hasColumn('oauth_clients', 'grant_types')) {
            $schema->table('oauth_clients', function (Blueprint $table): void {
                $table->text('grant_types')->nullable();
                // Passport 13 sets grant_types when creating clients, not the legacy flags.
                $table->boolean('personal_access_client')->default(false)->change();
                $table->boolean('password_client')->default(false)->change();
            });
        }

        DB::connection($this->getConnection())->table('oauth_clients')->whereNull('grant_types')->chunkById(200, function ($clients): void {
            foreach ($clients as $client) {
                $grants = [];

                if ($client->personal_access_client) {
                    $grants[] = 'personal_access';
                }

                if ($client->password_client) {
                    $grants[] = 'password';
                }

                if (! empty($client->redirect)) {
                    $grants[] = 'authorization_code';
                    $grants[] = 'implicit';
                }

                // Do not misidentify a user's bearer token as a client-credentials token
                // when the existing integer user ID happens to equal the client ID.
                if (! $client->personal_access_client && ! $client->password_client
                    && empty($client->user_id) && ! empty($client->secret)) {
                    $grants[] = 'client_credentials';
                }

                $grants[] = 'refresh_token';

                DB::connection($this->getConnection())->table('oauth_clients')->where('id', $client->id)->update([
                    'grant_types' => json_encode($grants, JSON_THROW_ON_ERROR),
                ]);
            }
        });
    }

    public function down(): void
    {
        $schema = Schema::connection($this->getConnection());

        if (! $schema->hasColumn('oauth_clients', 'personal_access_client')
            || ! $schema->hasColumn('oauth_clients', 'grant_types')) {
            return;
        }

        DB::connection($this->getConnection())->table('oauth_clients')->orderBy('id')->chunkById(200, function ($clients): void {
            foreach ($clients as $client) {
                $grants = json_decode($client->grant_types ?? '[]', true, flags: JSON_THROW_ON_ERROR);
                DB::connection($this->getConnection())->table('oauth_clients')->where('id', $client->id)->update([
                    'personal_access_client' => in_array('personal_access', $grants, true),
                    'password_client' => in_array('password', $grants, true),
                ]);
            }
        });

        $schema->table('oauth_clients', fn (Blueprint $table) => $table->dropColumn('grant_types'));
    }

    public function getConnection(): ?string
    {
        return $this->connection ?? config('passport.connection');
    }
};
