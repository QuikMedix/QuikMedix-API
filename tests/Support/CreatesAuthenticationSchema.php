<?php

namespace Tests\Support;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

trait CreatesAuthenticationSchema
{
    protected function createAuthenticationSchema(): void
    {
        require_once database_path('migrations/2014_10_12_000000_create_users_table.php');
        (new \CreateUsersTable)->up();
        Schema::table('users', function (Blueprint $table): void {
            $table->string('phone')->nullable()->unique();
            $table->integer('os')->default(0);
        });

        foreach (glob(database_path('migrations/2016_06_01_*.php')) as $path) {
            (require $path)->up();
        }

        (require database_path('migrations/2026_09_22_042529_add_grant_types_to_legacy_oauth_clients_table.php'))->up();
    }
}
