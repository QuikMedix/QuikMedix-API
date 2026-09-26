<?php

namespace Tests\Feature\Auth;

use App\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Schema;
use Tests\Support\CreatesAuthenticationSchema;
use Tests\TestCase;

class AccountManagementTest extends TestCase
{
    use CreatesAuthenticationSchema;

    private User $superadmin;

    private User $admin;

    private User $pharmacyAdmin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createAuthenticationSchema();
        Schema::table('users', function (Blueprint $table): void {
            $table->string('role')->default('user');
            $table->integer('isactive')->default(1);
            $table->integer('isblocked')->default(0);
            $table->integer('pharmacy_id')->nullable();
            $table->string('image')->nullable();
        });
        Schema::create('pharmacys', function (Blueprint $table): void {
            $table->increments('id');
            $table->integer('isactive')->default(1);
            $table->integer('isblocked')->default(0);
        });
        DB::table('pharmacys')->insert([['id' => 2], ['id' => 3]]);
        Redis::shouldReceive('get')->andReturn(null);
        Redis::shouldReceive('setex')->andReturn(true);

        $this->superadmin = $this->account('superadmin');
        $this->admin = $this->account('dispadmin');
        $this->pharmacyAdmin = $this->account('medic', 2);
    }

    private function account(string $role, ?int $pharmacyId = null): User
    {
        return User::factory()->create(['role' => $role, 'pharmacy_id' => $pharmacyId, 'isactive' => 1, 'isblocked' => 0]);
    }

    /**
     * Admin and dispatch-admin sessions pass two-factor authentication at login.
     */
    private function asAdmin(): static
    {
        return $this->actingAs($this->admin)->withSession(['user_2fa' => $this->admin->id]);
    }

    public function test_admin_staff_cannot_block_remove_or_demote_a_superadmin(): void
    {
        foreach (['block', 'remove', 'touser'] as $action) {
            $this->asAdmin()->post('/settings/users', ['user_id' => $this->superadmin->id, $action => 1])->assertForbidden();
        }

        $this->assertDatabaseHas('users', ['id' => $this->superadmin->id, 'role' => 'superadmin', 'isblocked' => 0]);
    }

    public function test_admin_staff_cannot_promote_anyone_to_admin(): void
    {
        $patient = $this->account('user', 2);

        $this->asAdmin()->post('/settings/drivers', ['user_id' => $patient->id, 'touseradmin' => 1])->assertForbidden();
        $this->asAdmin()->post('/settings/users', ['user_id' => $this->admin->id, 'touseradmin' => 1])->assertForbidden();

        $this->assertDatabaseHas('users', ['id' => $patient->id, 'role' => 'user']);
        $this->assertDatabaseHas('users', ['id' => $this->admin->id, 'role' => 'dispadmin']);
    }

    public function test_admin_staff_cannot_edit_a_superadmin_or_grant_themselves_superadmin(): void
    {
        $patient = $this->account('user', 2);

        $this->asAdmin()->get("/settings/users/edit/{$this->superadmin->id}")->assertForbidden();
        $this->asAdmin()->post("/settings/users/edit/{$this->superadmin->id}", ['save' => 1, 'phone' => '(555) 000-0000'])->assertForbidden();
        $this->asAdmin()->post("/settings/users/edit/{$patient->id}", ['save' => 1, 'role' => 'superadmin'])->assertForbidden();

        $this->assertDatabaseHas('users', ['id' => $patient->id, 'role' => 'user']);
    }

    public function test_pharmacy_admin_can_only_manage_accounts_of_their_own_pharmacy(): void
    {
        $otherPharmacyPatient = $this->account('user', 3);

        $this->actingAs($this->pharmacyAdmin)->post('/pharmacy/2/users', ['user_id' => $this->superadmin->id, 'touser' => 1])->assertForbidden();
        $this->actingAs($this->pharmacyAdmin)->post('/pharmacy/2/users', ['user_id' => $otherPharmacyPatient->id, 'tomedic' => 1])->assertForbidden();
        $this->actingAs($this->pharmacyAdmin)->post("/pharmacy/2/users/edit/{$this->superadmin->id}", ['save' => 1, 'phone' => '(555) 000-0000'])->assertForbidden();

        $this->assertDatabaseHas('users', ['id' => $this->superadmin->id, 'role' => 'superadmin', 'phone' => $this->superadmin->phone]);
        $this->assertDatabaseHas('users', ['id' => $otherPharmacyPatient->id, 'role' => 'user']);
    }

    public function test_the_rules_still_allow_legitimate_management(): void
    {
        $ownPatient = $this->account('user', 2);

        $this->assertTrue(Gate::forUser($this->pharmacyAdmin)->allows('manage-account', [$ownPatient->id, 2]));
        $this->assertTrue(Gate::forUser($this->admin)->allows('manage-account', [$ownPatient->id]));
        $this->assertTrue(Gate::forUser($this->superadmin)->allows('manage-account', [$this->admin->id]));
        $this->assertTrue(Gate::forUser($this->admin)->allows('assign-role', 'logist'));
        $this->assertTrue(Gate::forUser($this->superadmin)->allows('assign-role', 'admin'));
        $this->assertTrue(Gate::forUser($this->pharmacyAdmin)->allows('assign-role', 'medic'));
        $this->assertFalse(Gate::forUser($this->pharmacyAdmin)->allows('assign-role', 'logist'));
        $this->assertFalse(Gate::forUser($this->superadmin)->allows('assign-role', 'owner'), 'unknown roles are never assigned');
    }

    public function test_the_payroll_page_is_for_admin_staff_only(): void
    {
        $this->actingAs($this->pharmacyAdmin)->get('/payroll')->assertForbidden();
        $this->actingAs($this->account('user', 2))->get('/payroll')->assertForbidden();
    }
}
