<?php

namespace App\Http\Controllers;

use App\Actions\GeocodeAddress;
use App\Actions\LogUserChanges;
use App\Actions\SendWelcomeSms;
use App\Http\Controllers\Concerns\RendersLegacyPages;
use App\Http\Requests\StoreFacilityRequest;
use App\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * A pharmacy's facilities (care homes and similar accounts that receive orders for several people).
 * Formerly the facilitys* actions of LexaAdmin.
 */
class FacilityController extends Controller
{
    use RendersLegacyPages;

    private const PER_PAGE = 30;

    public function __construct()
    {
        $this->middleware(['auth', '2fa', 'active']);
    }

    public function index(Request $request, string $pharmacy_id): View|array
    {
        $this->authorize('manage-pharmacy', $pharmacy_id);

        $facilities = User::where('role', 'facility')->where('pharmacy_id', $pharmacy_id);
        $search = (string) $request->query('search', '');
        if ($search !== '') {
            $facilities->where(fn ($query) => $query
                ->where(DB::raw("CONCAT(name, ' ', last_name)"), 'LIKE', '%'.$search.'%')
                ->orWhere('email', 'LIKE', '%'.$search.'%')
                ->orWhere('phone', 'LIKE', '%'.$search.'%'));
        }
        $page = max(1, (int) $request->query('page', 1));
        $maxPages = (int) ceil($facilities->count() / self::PER_PAGE);

        return $this->page($request, view('facilitys.users', [
            'pages' => $this->pageLinks($page, $maxPages),
            'page0' => $page,
            'search' => $search,
            'users' => $facilities->orderBy('users.id', 'desc')->offset(($page - 1) * self::PER_PAGE)->limit(self::PER_PAGE)->get(),
            'pharmacy_id' => $pharmacy_id,
            'error' => '',
            'title' => 'Patients', 'br1' => 'Patients', 'br2' => 'Users',
        ]));
    }

    /**
     * Activate, block, unblock or remove one of this pharmacy's facilities. Removing detaches the
     * facility from the pharmacy; its account and orders are kept.
     */
    public function updateStatus(Request $request, string $pharmacy_id): RedirectResponse
    {
        $facilityId = $request->input('user_id');
        $this->authorize('manage-pharmacy-facility', [$pharmacy_id, $facilityId]);

        $changes = array_filter([
            'isactive' => $request->input('activate') > 0 ? 1 : null,
            'isblocked' => $request->input('block') > 0 ? 1 : ($request->input('unblock') > 0 ? 0 : null),
        ], fn ($value) => $value !== null);
        if ($request->input('remove') > 0) {
            $changes['pharmacy_id'] = null;
        }
        if ($changes !== []) {
            DB::table('users')->where('id', $facilityId)->update($changes);
        }

        return redirect("facilitys/$pharmacy_id")->with('success', $request->input('remove') > 0 ? 'Facility removed.' : 'Facility updated.');
    }

    public function create(Request $request, string $pharmacy_id): View|array|RedirectResponse
    {
        $this->authorize('manage-pharmacy', $pharmacy_id);
        if (Auth::user()->pharmacy_balance_ban()) {
            return redirect('billing/'.Auth::user()->pharmacy_id);
        }

        $input = array_fill_keys(['name', 'last_name', 'email', 'phone', 'home_phone', 'birth_date', 'image', 'address', 'zip', 'apartment'], '');
        $input = array_merge($input, array_intersect_key($request->old(), $input), ['pharmacy' => $pharmacy_id]);

        return $this->page($request, view('facilitys.user_add', [
            'pharmacys' => DB::table('pharmacys')->get(),
            'title' => 'Facilitys Add', 'br1' => 'Facilitys', 'br2' => 'Patient Add',
            'alert' => '',
            'input' => $input,
        ]));
    }

    public function store(StoreFacilityRequest $request, string $pharmacy_id, GeocodeAddress $geocoder, SendWelcomeSms $welcomeSms): RedirectResponse
    {
        if (Auth::user()->pharmacy_balance_ban()) {
            return redirect('billing/'.Auth::user()->pharmacy_id);
        }

        $geo = $geocoder->lookup(trim($request->input('address').' '.$request->input('zip')));
        $password = bin2hex(random_bytes(4));

        $facilityId = DB::table('users')->insertGetId([
            ...$request->safe()->only(['name', 'phone', 'birth_date', 'zip', 'apartment']),
            'role' => 'facility',
            'isactive' => '1',
            'last_name' => $request->input('last_name') ?: ' ',
            'email' => $request->input('email') ?: 'facilitys'.DB::table('users')->max('id').'@'.config('branding.account_email_domain'),
            'home_phone' => $request->input('home_phone') ?: null,
            'image' => $this->storeUpload($request->file('image'), 'users') ?? '',
            'address' => $geo['formatted_address'],
            'location' => $geo['location'],
            'password' => Hash::make($password),
            'pharmacy_id' => $pharmacy_id,
        ]);
        $welcomeSms->handle(DB::table('users')->where('id', $facilityId)->first(), $password);

        if ($request->filled('order_add')) {
            return redirect("orders/$pharmacy_id/add?facility=$facilityId");
        }

        return redirect("facilitys/$pharmacy_id")->with('success', 'Facility added.');
    }

    public function edit(Request $request, string $pharmacy_id, string $user_id): View|array
    {
        $this->authorize('manage-pharmacy-facility', [$pharmacy_id, $user_id]);

        return $this->page($request, view('facilitys.user_edit', [
            'user' => DB::table('users')->where('id', $user_id)->first(),
            'user_actions' => DB::table('action_log')->where('user_id', $user_id)->get(),
            'pharmacys' => DB::table('pharmacys')->get(),
            'additional_recipients' => DB::table('additional_recipients')->where('user_id', $user_id)->get(),
            'patients' => DB::table('users')->where('pharmacy_id', $pharmacy_id)->where('role', 'user')->get(),
            'title' => 'Patients Edit', 'br1' => 'Patients', 'br2' => 'Patients Edit',
            'alert' => '',
        ]));
    }

    /**
     * The edit page posts four forms here: the profile, "add recipient", "add patients as recipients" and "remove recipient".
     */
    public function update(Request $request, string $pharmacy_id, string $user_id, GeocodeAddress $geocoder, LogUserChanges $logChanges): RedirectResponse
    {
        $this->authorize('manage-pharmacy-facility', [$pharmacy_id, $user_id]);
        $back = redirect("facilitys/$pharmacy_id/edit/$user_id");

        if ($request->filled('patients_db')) {
            $patients = DB::table('users')->where('pharmacy_id', $pharmacy_id)->where('role', 'user')
                ->whereIn('id', (array) $request->input('patients_db'))->get();
            foreach ($patients as $patient) {
                DB::table('additional_recipients')->insert(['user_id' => $user_id, 'family_type' => 'Additional Recipient', 'family_name' => $patient->name.' '.$patient->last_name, 'family_phone' => $patient->phone]);
            }

            return $back->with('success', 'Recipients added.');
        }

        if ($request->input('additional_recipients') > 0) {
            $recipient = $request->validate([
                'family_type' => ['required', 'string', 'max:255'],
                'family_name' => ['required', 'string', 'max:255'],
                'family_name2' => ['required', 'string', 'max:255'],
                'family_phone' => ['required', 'string', 'max:20'],
            ]);
            DB::table('additional_recipients')->insert([
                'user_id' => $user_id,
                'family_type' => $recipient['family_type'],
                'family_name' => $recipient['family_name'].' '.$recipient['family_name2'],
                'family_phone' => $recipient['family_phone'],
            ]);

            return $back->with('success', 'Recipient added.');
        }

        if ($request->input('additional_recipient_remove') > 0) {
            DB::table('additional_recipients')->where('id', $request->input('additional_recipient_remove'))->where('user_id', $user_id)->delete();

            return $back->with('success', 'Recipient removed.');
        }

        if ($request->input('save') > 0) {
            $this->saveProfile($request, $pharmacy_id, $user_id, $geocoder, $logChanges);

            return $back->with('success', 'Facility saved.');
        }

        return $back;
    }

    private function saveProfile(Request $request, string $pharmacyId, string $facilityId, GeocodeAddress $geocoder, LogUserChanges $logChanges): void
    {
        $inThisPharmacy = fn (string $column) => Rule::unique('users', $column)->where('pharmacy_id', $pharmacyId)->ignore($facilityId);
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'string', 'email', 'max:191', $inThisPharmacy('email')],
            'phone' => ['required', 'string', 'max:20', $inThisPharmacy('phone')],
            'home_phone' => ['nullable', 'string', 'max:20'],
            'address' => ['required', 'string', 'max:255'],
            'zip' => ['required', 'string', 'max:25'],
            'apartment' => ['nullable', 'string', 'max:25'],
            'image' => ['nullable', 'file', 'mimes:jpeg,jpg,png', 'max:6000'],
        ], [
            'email.unique' => 'A facility with this e-mail already exists in this pharmacy.',
            'phone.unique' => 'A facility with this phone number already exists in this pharmacy.',
        ]);

        $facility = DB::table('users')->where('id', $facilityId)->first();
        $geo = $geocoder->lookup(trim($validated['address'].' '.$validated['zip']));
        $logChanges->handle($request, $geo['formatted_address'], $facilityId);

        $image = $this->storeUpload($request->file('image'), 'users') ?? $facility->image;
        if ($request->input('remove_photo') > 0) {
            $image = '/images/users/default-user-image.png';
        }

        DB::table('users')->where('id', $facilityId)->update([
            ...Arr::only($validated, ['name', 'last_name', 'phone', 'zip', 'apartment']),
            'email' => ($validated['email'] ?? null) ?: 'facilitys'.((int) DB::table('users')->max('id') + 1).'@'.config('branding.account_email_domain'),
            'home_phone' => ($validated['home_phone'] ?? null) ?: null,
            'image' => $image,
            'address' => $geo['formatted_address'],
            'location' => $geo['location'],
        ]);
    }
}
