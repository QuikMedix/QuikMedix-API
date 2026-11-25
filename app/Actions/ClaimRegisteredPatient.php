<?php

namespace App\Actions;

use App\Models\ActionLog;
use App\Support\Branding;
use App\Support\Sms;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * A patient can register in the app before any pharmacy knows them. When a pharmacy then adds that
 * phone number, the account the patient made is attached to the pharmacy; a second account with the
 * same phone could never be signed in to, because sign-in finds the older one.
 */
class ClaimRegisteredPatient
{
    public function __construct(private Sms $sms) {}

    /**
     * Attaches the app-registered account with this phone to the pharmacy.
     *
     * Registration does not prove who owns a phone number, so the account is handed over the way a
     * new one is: its password is replaced and texted to the number, and it is signed out everywhere.
     * Whoever registered the number without owning it loses the account before it shows any order.
     *
     * @param  array<string, mixed>  $details  what the pharmacy entered; blanks keep the patient's own values
     * @return int|null the account's id, or null when there is none to claim and the caller creates one
     */
    public function handle(?string $phone, int|string $pharmacyId, array $details = []): ?int
    {
        $account = $phone === null || trim($phone) === '' ? null : $this->unclaimed($phone);
        if ($account === null) {
            return null;
        }

        $password = bin2hex(random_bytes(4));
        $details = array_filter($details, fn ($value) => $value !== null && $value !== '');
        $changes = array_intersect_key([
            ...$details,
            // The pharmacy's spelling of the number is the one its own lookups use.
            'phone' => $phone,
            'pharmacy_id' => $pharmacyId,
            'isactive' => 1,
            'password' => Hash::make($password),
            'remember_token' => null,
            'device_token' => null,
        ], (array) $account);

        $attached = DB::transaction(function () use ($account, $changes, $pharmacyId): bool {
            // Guarded on the pharmacy still being empty, so two pharmacies adding at once cannot both win.
            if (DB::table('users')->where('id', $account->id)->whereNull('pharmacy_id')->update($changes) === 0) {
                return false;
            }
            if (DB::getSchemaBuilder()->hasTable('oauth_access_tokens')) {
                DB::table('oauth_access_tokens')->where('user_id', $account->id)->update(['revoked' => true]);
            }
            ActionLog::query()->insert([
                'type' => 'attached to pharmacy',
                'user_id' => $account->id,
                'action_user_id' => Auth::id() ?? $account->id,
                'comment' => "Pharmacy #$pharmacyId added a phone number that had registered in the app. The password was replaced.",
            ]);

            return true;
        });
        if (! $attached) {
            return null;
        }

        $pharmacyName = DB::table('pharmacys')->where('id', $pharmacyId)->value('name');
        $this->sms->send($phone, ($pharmacyName ? 'From: '.$pharmacyName." \n" : '')
            .'Hello, '.($details['name'] ?? $account->name).'. '.($pharmacyName ?: 'Your pharmacy')
            ." added your QuikMedix account to its patients. For your security the password was replaced. \nLogin: ".$phone
            ."\nPassword: ".$password."\n".Branding::appAccessMessage()." \nBest regards, QuikMedix");

        return (int) $account->id;
    }

    /**
     * The patient account with this phone that no pharmacy has ever had: registered in the app, never
     * removed from a pharmacy and without orders. A removed patient keeps another pharmacy's history,
     * so that account is not handed to the next pharmacy.
     */
    private function unclaimed(string $phone): ?object
    {
        return DB::table('users')
            ->where('role', 'user')
            ->whereNull('pharmacy_id')
            ->whereIn('phone', $this->spellings($phone))
            ->whereNotExists(fn ($orders) => $orders->selectRaw('1')->from('orders')->whereColumn('orders.user_id', 'users.id'))
            ->whereNotExists(fn ($removed) => $removed->selectRaw('1')->from('deleted_patients')->whereColumn('deleted_patients.user_id', 'users.id'))
            ->orderBy('id')
            ->first();
    }

    /**
     * The ways one US number is written: the app and the pharmacy pages store "(555) 010-3002",
     * older clients sent the digits alone.
     *
     * @return list<string>
     */
    private function spellings(string $phone): array
    {
        $digits = preg_replace('/\D/', '', $phone);
        if (strlen($digits) === 11 && $digits[0] === '1') {
            $digits = substr($digits, 1);
        }
        if (strlen($digits) !== 10) {
            return [$phone];
        }
        [$area, $exchange, $line] = [substr($digits, 0, 3), substr($digits, 3, 3), substr($digits, 6)];

        return array_values(array_unique([
            $phone, "($area) $exchange-$line", $digits, "1$digits", "+1$digits", "$area-$exchange-$line",
        ]));
    }
}
