<?php

namespace Tests\Feature\Auth;

use App\User;
use Illuminate\Support\Facades\Gate;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RoleGatesTest extends TestCase
{
    public static function roles(): array
    {
        // role, pharmacy of the user, is admin staff, may manage pharmacy #2
        return [
            'superadmin' => ['superadmin', null, true, true],
            'admin' => ['admin', null, true, true],
            'dispatch admin' => ['dispadmin', null, true, true],
            'medic of pharmacy 2' => ['medic', 2, false, true],
            'medic of another pharmacy' => ['medic', 3, false, false],
            'logist' => ['logist', null, false, false],
            'driver of pharmacy 2' => ['driver', 2, false, false],
            'patient of pharmacy 2' => ['user', 2, false, false],
        ];
    }

    #[DataProvider('roles')]
    public function test_role_gates(string $role, ?int $pharmacyId, bool $isAdmin, bool $managesPharmacy): void
    {
        $user = new User(['role' => $role, 'pharmacy_id' => $pharmacyId]);

        $this->assertSame($isAdmin, Gate::forUser($user)->allows('admin'));
        $this->assertSame($managesPharmacy, Gate::forUser($user)->allows('manage-pharmacy', 2));
        $this->assertSame($managesPharmacy, Gate::forUser($user)->allows('manage-pharmacy', '2'), 'route parameters are strings');
    }

    public function test_has_any_role(): void
    {
        $driver = new User(['role' => 'driver']);

        $this->assertTrue($driver->hasAnyRole('admin', 'driver'));
        $this->assertFalse($driver->hasAnyRole('admin', 'user'));
    }
}
