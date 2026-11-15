<?php

namespace App\Actions;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class LogUserChanges
{
    /**
     * Writes an action_log row for each profile field the request changes. For forms that submit
     * and save the whole profile; the secondary addresses are only compared when the caller passes them.
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

        $this->record($changes, $userId);
    }

    /**
     * For the mobile profile, which saves only the fields that were filled in: a field that was
     * left out or blank is not a change, and neither is one that profile does not save.
     *
     * @param  string|null  $address  the geocoded address, empty when no address was sent
     */
    public function handleSubmitted(Request $request, ?string $address, int|string $userId): void
    {
        $user = DB::table('users')->where('id', $userId)->first();
        $changes = [];

        foreach (['name', 'last_name', 'email', 'phone'] as $field) {
            if ($this->submittedChange($request, $user, $field)) {
                $changes[] = ['change '.$field, $user->{$field}, $request->input($field)];
            }
        }
        if (! empty($address) && $address != $user->address) {
            $changes[] = ['change address', $user->address, $request->input('address')];
        }
        foreach (['zip', 'apartment', 'car_info'] as $field) {
            if ($this->submittedChange($request, $user, $field)) {
                $changes[] = ['change '.$field, $user->{$field}, $request->input($field)];
            }
        }

        $this->record($changes, $userId);
    }

    private function submittedChange(Request $request, object $user, string $field): bool
    {
        return $request->input($field) != '' && $request->input($field) != $user->{$field};
    }

    /**
     * @param  list<array{string, mixed, mixed}>  $changes  type, old value, new value
     */
    private function record(array $changes, int|string $userId): void
    {
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
