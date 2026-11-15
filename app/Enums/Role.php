<?php

namespace App\Enums;

/**
 * The value stored in users.role. This is the one list of roles; routes name them in
 * `role:` middleware and the gates in AuthServiceProvider build on them.
 */
enum Role: string
{
    case Superadmin = 'superadmin';
    case Admin = 'admin';
    case DispatchAdmin = 'dispadmin';
    case Logist = 'logist';
    case Sale = 'sale';
    case Medic = 'medic';
    case Driver = 'driver';
    case User = 'user';
    case Facility = 'facility';

    /**
     * QuikMedix administration staff: superadmin, admin and dispatch admin.
     */
    public function isAdmin(): bool
    {
        return in_array($this, self::admins(), true);
    }

    /**
     * @return list<self>
     */
    public static function admins(): array
    {
        return [self::Superadmin, self::Admin, self::DispatchAdmin];
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * @return list<string>
     */
    public static function adminValues(): array
    {
        return array_column(self::admins(), 'value');
    }
}
