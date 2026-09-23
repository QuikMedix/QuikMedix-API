<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Laravel\Passport\Passport;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        // 'App\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function register(): void
    {
        parent::register();

        // Preserve existing integer client identifiers and OAuth endpoints.
        Passport::$clientUuids = false;
        Passport::$registersJsonApiRoutes = true;
        Passport::$deviceCodeGrantEnabled = false;
        Passport::enablePasswordGrant();
    }

    public function boot(): void
    {
        $this->registerPolicies();
        Passport::authorizationView('auth.oauth.authorize');
    }
}
