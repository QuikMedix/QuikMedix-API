<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Lets a signed-in user delete their own account from the mobile app
 * (required by App Store Review Guideline 5.1.1(v)).
 *
 * The users row is kept so past orders and payouts still point at a valid id,
 * but every piece of personal data is erased and the account can never sign in again.
 */
class DeleteAccountController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();
        $row = DB::table('users')->where('id', $user->id)->first();

        DB::transaction(function () use ($user, $row): void {
            DB::table('users')->where('id', $user->id)->update(array_intersect_key([
                'name' => 'Deleted',
                'last_name' => 'User',
                'email' => "deleted-{$user->id}@deleted.invalid",
                'phone' => "deleted-{$user->id}",
                'password' => Hash::make(Str::random(64)),
                'image' => null,
                'avatar' => null,
                'driving_license' => null,
                'driving_license_img' => null,
                'identification_cards' => null,
                'car_info' => null,
                'device_token' => null,
                'remember_token' => null,
                'isactive' => 0,
                'isblocked' => 1,
            ], (array) $row));

            // Sign the user out on every device.
            if (DB::getSchemaBuilder()->hasTable('oauth_access_tokens')) {
                DB::table('oauth_access_tokens')->where('user_id', $user->id)->update(['revoked' => true]);
            }
        });

        foreach (['image', 'driving_license_img'] as $column) {
            $path = $row->{$column} ?? null;
            if ($path && ! str_contains($path, '..') && is_file(public_path($path))) {
                @unlink(public_path($path));
            }
        }

        Log::info('User deleted their account', ['user_id' => $user->id]);

        return response()->json(['message' => 'Account deleted']);
    }
}
