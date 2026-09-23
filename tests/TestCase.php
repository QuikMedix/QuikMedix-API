<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Http;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    private static array $passportKeys = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        if (! $this->app->environment('testing') || config('database.default') !== 'sqlite'
            || config('database.connections.sqlite.database') !== ':memory:') {
            throw new RuntimeException('Tests require an isolated in-memory SQLite database.');
        }

        if (self::$passportKeys === []) {
            $key = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
            openssl_pkey_export($key, $privateKey);
            self::$passportKeys = [
                'passport.private_key' => $privateKey,
                'passport.public_key' => openssl_pkey_get_details($key)['key'],
            ];
        }

        config(self::$passportKeys);
        config(['passport.connection' => 'sqlite']);
        Http::preventStrayRequests();
    }
}
