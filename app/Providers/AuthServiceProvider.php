<?php

namespace App\Providers;

use App\User;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
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

        Gate::define('admin', fn (User $user): bool => $user->isAdmin());
        Gate::define('manage-pharmacy', fn (User $user, int|string|null $pharmacyId = null): bool => $user->canManagePharmacy($pharmacyId));
        // Admin staff manage any driver or patient; a pharmacy user only those of their own pharmacy.
        $managesMember = fn (User $user, int|string|null $pharmacyId, int|string|null $memberId, string $role): bool => $user->canManagePharmacy($pharmacyId)
            && DB::table('users')->where('id', $memberId)->where('role', $role)
                ->when(! $user->isAdmin(), fn ($query) => $query->where('pharmacy_id', $pharmacyId))
                ->exists();
        Gate::define('manage-pharmacy-driver', fn (User $user, int|string|null $pharmacyId = null, int|string|null $driverId = null): bool => $managesMember($user, $pharmacyId, $driverId, 'driver'));
        Gate::define('manage-pharmacy-patient', fn (User $user, int|string|null $pharmacyId = null, int|string|null $patientId = null): bool => $managesMember($user, $pharmacyId, $patientId, 'user'));
        // A pharmacy user may only touch orders of their own pharmacy; other roles keep their existing checks.
        Gate::define('access-order', fn (User $user, int|string|null ...$orderIds): bool => $user->role !== 'medic'
            || DB::table('orders')->whereIn('id', $orderIds)->where('pharmacy_id', $user->pharmacy_id)->count() === count(array_unique($orderIds)));
        // Only a superadmin may touch admin-tier accounts; staff manage everyone else; a pharmacy user only accounts of their pharmacy.
        Gate::define('manage-account', function (User $user, int|string|null $accountId, int|string|null $pharmacyId = null): bool {
            $account = DB::table('users')->where('id', $accountId)->first(['role', 'pharmacy_id']);
            if ($account === null) {
                return false;
            }
            if (in_array($account->role, User::ADMIN_ROLES, true)) {
                return $user->role === 'superadmin';
            }

            return $user->isAdmin() || ($user->canManagePharmacy($pharmacyId) && (string) $account->pharmacy_id === (string) $pharmacyId);
        });
        Gate::define('assign-role', fn (User $user, string $role): bool => match (true) {
            ! in_array($role, User::ROLES, true) => false,
            $user->role === 'superadmin' => true,
            $user->isAdmin() => ! in_array($role, User::ADMIN_ROLES, true),
            default => $user->role === 'medic' && in_array($role, ['user', 'driver', 'medic'], true),
        });
        Gate::define('manage-pharmacy-facility', fn (User $user, int|string|null $pharmacyId = null, int|string|null $facilityId = null): bool => $managesMember($user, $pharmacyId, $facilityId, 'facility'));
    }
}
