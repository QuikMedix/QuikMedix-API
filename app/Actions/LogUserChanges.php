<?php

namespace App\Actions;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class LogUserChanges
{
    /**
     * Writes an action_log row for each profile field the request changes.
     * The secondary addresses are only compared when the caller passes them.
     */
    public function handle(Request $request, ?string $address, int|string $userId, ?string $address2 = null, ?string $address3 = null): void
    {
        $user = DB::table('users')->where('id', $userId)->first();
        $changes = [];

        foreach (['name', 'last_name', 'email', 'phone', 'zip', 'apartment'] as $field) {
            if ($request->input($field) != $user->{$field}) {
                $changes[] = ['change '.$field, $user->{$field}, $request->input($field)];
            }
        }
        if ($address != $user->address) {
            $changes[] = ['change address', $user->address, $request->input('address')];
        }
        foreach (['address2' => $address2, 'address3' => $address3] as $column => $value) {
            if ($value !== null && $value != $user->{$column}) {
                $changes[] = ['change address', $user->address, $request->input('address')];
            }
        }
        foreach (['car_info', 'pharmacy_id'] as $field) {
            if (!empty($request->input($field)) && $request->input($field) != $user->{$field}) {
                $changes[] = ['change '.$field, $user->{$field}, $request->input($field)];
            }
        }

        foreach ($changes as [$type, $from, $to]) {
            DB::table('action_log')->insert([
                'type' => $type,
                'comment' => 'from '.$from.' to '.$to,
                'user_id' => $userId,
                'action_user_id' => Auth::id(),
            ]);
        }
    }
}
