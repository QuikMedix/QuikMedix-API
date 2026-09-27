<?php

namespace App\Http\Controllers;

use App\Actions\GeocodeAddress;
use App\Actions\LogUserChanges;
use App\Actions\SendWelcomeSms;
use App\Http\Controllers\Concerns\RendersLegacyPages;
use App\Http\Requests\StorePatientRequest;
use App\Support\GeoPoint;
use App\User;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * A pharmacy's patients: lists, add, edit and family members. Formerly the patients* actions of LexaAdmin.
 */
class PatientController extends Controller
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

        $patients = User::where('role', 'user')->where('pharmacy_id', $pharmacy_id);
        [$patients, $search, $page, $maxPages] = $this->searchAndPaginate($request, $patients);

        return $this->page($request, view('patients.users', [
            'pages' => $this->pageLinks($page, $maxPages),
            'page0' => $page,
            'search' => $search,
            'users' => $patients->orderBy('users.id', 'desc')->offset(($page - 1) * self::PER_PAGE)->limit(self::PER_PAGE)->get(),
            'pharmacy_id' => $pharmacy_id,
            'error' => '',
            'title' => 'Patients', 'br1' => 'Patients', 'br2' => 'Users',
        ]));
    }

    public function removed(Request $request, string $pharmacy_id): View|array
    {
        $this->authorize('manage-pharmacy', $pharmacy_id);

        $patients = DB::table('deleted_patients')->join('users', 'deleted_patients.user_id', '=', 'users.id')
            ->select('deleted_patients.medic_id', 'users.name', 'users.last_name', 'users.email', 'users.phone', 'users.home_phone', 'users.role', 'users.os', 'deleted_patients.reason')
            ->where('users.pharmacy_id', $pharmacy_id);
        [$patients, $search, $page, $maxPages] = $this->searchAndPaginate($request, $patients);

        return $this->page($request, view('patients.removed', [
            'pages' => $this->pageLinks($page, $maxPages),
            'page0' => $page,
            'search' => $search,
            'users' => $patients->orderBy('users.id', 'asc')->offset(($page - 1) * self::PER_PAGE)->limit(self::PER_PAGE)->get(),
            'pharmacy_id' => $pharmacy_id,
            'error' => '',
            'title' => 'Removed Patients', 'br1' => 'Patients', 'br2' => 'Removed Patients',
        ]));
    }

    /**
     * Activate, block, unblock or remove one of this pharmacy's patients. Removing requires the user's password.
     */
    public function updateStatus(Request $request, string $pharmacy_id): RedirectResponse
    {
        $patientId = $request->input('user_id');
        $this->authorize('manage-pharmacy-patient', [$pharmacy_id, $patientId]);

        $changes = array_filter([
            'isactive' => $request->input('activate') > 0 ? 1 : null,
            'isblocked' => $request->input('block') > 0 ? 1 : ($request->input('unblock') > 0 ? 0 : null),
        ], fn ($value) => $value !== null);
        if ($changes !== []) {
            DB::table('users')->where('id', $patientId)->update($changes);
        }

        if ($request->input('remove') > 0) {
            if (! Hash::check((string) $request->input('password'), Auth::user()->password)) {
                return redirect("patients/$pharmacy_id")->with('error', 'The patient was not removed: your password is incorrect.');
            }
            DB::transaction(function () use ($request, $patientId): void {
                DB::table('deleted_patients')->insert(['user_id' => $patientId, 'medic_id' => Auth::id(), 'reason' => $request->input('reason')]);
                DB::table('users')->where('id', $patientId)->update(['pharmacy_id' => null]);
            });

            return redirect("patients/$pharmacy_id")->with('success', 'Patient removed.');
        }

        return redirect("patients/$pharmacy_id");
    }

    public function create(Request $request, string $pharmacy_id): View|array|RedirectResponse
    {
        $this->authorize('manage-pharmacy', $pharmacy_id);
        if (Auth::user()->pharmacy_balance_ban()) {
            return redirect('billing/'.Auth::user()->pharmacy_id);
        }

        $input = array_fill_keys(['name', 'last_name', 'email', 'phone', 'home_phone', 'birth_date', 'image', 'address', 'zip', 'apartment'], '');
        $input = array_merge($input, array_intersect_key($request->old(), $input), ['pharmacy' => $pharmacy_id]);

        return $this->page($request, view('patients.user_add', [
            'pharmacys' => DB::table('pharmacys')->get(),
            'title' => 'Patients Add', 'br1' => 'Patients', 'br2' => 'Patient Add',
            'alert' => '',
            'input' => $input,
        ]));
    }

    public function store(StorePatientRequest $request, string $pharmacy_id, GeocodeAddress $geocoder, SendWelcomeSms $welcomeSms): RedirectResponse
    {
        if (Auth::user()->pharmacy_balance_ban()) {
            return redirect('billing/'.Auth::user()->pharmacy_id);
        }

        $geo = $geocoder->lookup(trim($request->input('address').' '.$request->input('zip')));
        $password = bin2hex(random_bytes(4));
        $email = $request->input('email') ?: 'patients'.DB::table('users')->max('id').'@'.config('branding.account_email_domain');

        $patientId = DB::table('users')->insertGetId([
            ...$request->safe()->only(['name', 'last_name', 'phone', 'birth_date', 'zip', 'apartment']),
            'isactive' => '1',
            'email' => $email,
            'home_phone' => $request->input('home_phone') ?: null,
            'image' => $this->storeUpload($request->file('image'), 'users') ?? '',
            'address' => $geo['formatted_address'],
            'location' => $geo['location'],
            'password' => Hash::make($password),
            'pharmacy_id' => $pharmacy_id,
        ]);
        $welcomeSms->handle(DB::table('users')->where('id', $patientId)->first(), $password);

        if ($request->filled('order_add')) {
            return redirect("orders/$pharmacy_id/add?patient=$patientId");
        }

        return redirect("patients/$pharmacy_id")->with('success', 'Patient added.');
    }

    public function edit(Request $request, string $pharmacy_id, string $user_id): View|array
    {
        $this->authorize('manage-pharmacy-patient', [$pharmacy_id, $user_id]);

        $patient = DB::table('users')->where('id', $user_id)->first();
        $zone = empty($patient->location) ? null
            : DB::table('area')->whereRaw('ST_CONTAINS(polygon, POINT(?, ?))', GeoPoint::bindings($patient->location))->select('area.id', 'area.name')->first();

        return $this->page($request, view('patients.user_edit', [
            'user' => $patient,
            'orders' => $this->recentOrders($user_id),
            'orders_stat' => DB::table('orders')->where('user_id', $user_id)->where('statuse_id', 4)
                ->select(DB::raw('count(distinct id) as count'), DB::raw('sum(copay) as copay'))->first(),
            'user_zone' => $zone,
            'user_actions' => DB::table('action_log')->where('user_id', $user_id)->get(),
            'pharmacys' => DB::table('pharmacys')->get()->keyBy('id'),
            'family_members' => DB::table('family_members')->where('user_id', $user_id)->get(),
            'title' => 'Patients Edit', 'br1' => 'Patients', 'br2' => 'Patients Edit',
            'alert' => '',
        ]));
    }

    /**
     * The edit page posts three forms here: the profile, "add family member" and "remove family member".
     */
    public function update(Request $request, string $pharmacy_id, string $user_id, GeocodeAddress $geocoder, LogUserChanges $logChanges): RedirectResponse
    {
        $this->authorize('manage-pharmacy-patient', [$pharmacy_id, $user_id]);
        $back = redirect("patients/$pharmacy_id/edit/$user_id");

        if ($request->input('family_members') > 0) {
            $family = $request->validate([
                'family_type' => ['required', 'string', 'max:255'],
                'family_name' => ['required', 'string', 'max:255'],
                'family_phone' => ['required', 'string', 'max:20'],
                'family_address' => ['required', 'string', 'max:255'],
            ]);
            $location = $geocoder->handle($family['family_address'], 'family_address');
            DB::table('family_members')->insert($family + ['user_id' => $user_id, 'location' => $location]);

            return $back->with('success', 'Family member added.');
        }

        if ($request->input('family_member_remove') > 0) {
            DB::table('family_members')->where('id', $request->input('family_member_remove'))->where('user_id', $user_id)->delete();

            return $back->with('success', 'Family member removed.');
        }

        if ($request->input('save') > 0) {
            $this->saveProfile($request, $pharmacy_id, $user_id, $geocoder, $logChanges);

            return $back->with('success', 'Patient saved.');
        }

        return $back;
    }

    /**
     * Family members as <option>s for the order form's recipient picker.
     */
    public function familyOptions(string $user_id): Response
    {
        return $this->recipientOptions($user_id, 'family_members', 'Select Family member...', true);
    }

    /**
     * Additional (facility) recipients as <option>s for the order form's recipient picker.
     */
    public function additionalRecipientOptions(string $user_id): Response
    {
        return $this->recipientOptions($user_id, 'additional_recipients', 'Add Facility patients...', false);
    }

    private function recipientOptions(string $patientId, string $table, string $placeholder, bool $withAddress): Response
    {
        $this->authorize('manage-pharmacy-patient', [Auth::user()->pharmacy_id, $patientId]);

        $html = '<option value="">'.e($placeholder).'</option>';
        foreach (DB::table($table)->where('user_id', $patientId)->get() as $member) {
            $label = $member->family_name.', '.$member->family_phone.' ('.$member->family_type.')'.($withAddress ? ', '.$member->family_address : '');
            $html .= '<option value="'.e($member->id).'">'.e($label).'</option>';
        }

        return response($html);
    }

    private function saveProfile(Request $request, string $pharmacyId, string $patientId, GeocodeAddress $geocoder, LogUserChanges $logChanges): void
    {
        $inThisPharmacy = fn (string $column) => Rule::unique('users', $column)->where('pharmacy_id', $pharmacyId)->ignore($patientId);
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'string', 'email', 'max:191', $inThisPharmacy('email')],
            'phone' => ['required', 'string', 'max:20', $inThisPharmacy('phone')],
            'home_phone' => ['nullable', 'string', 'max:20'],
            'primary_address' => ['nullable', 'integer'],
            'address' => ['required', 'string', 'max:255'],
            'zip' => ['required', 'string', 'max:25'],
            'address2' => ['nullable', 'string', 'max:255'],
            'address3' => ['nullable', 'string', 'max:255'],
            'zip2' => ['nullable', 'string', 'max:25'],
            'zip3' => ['nullable', 'string', 'max:25'],
            'apartment' => ['nullable', 'string', 'max:25'],
            'apartment2' => ['nullable', 'string', 'max:25'],
            'apartment3' => ['nullable', 'string', 'max:25'],
            'image' => ['nullable', 'file', 'mimes:jpeg,jpg,png', 'max:6000'],
        ], [
            'email.unique' => 'A patient with this e-mail already exists in this pharmacy.',
            'phone.unique' => 'A patient with this phone number already exists in this pharmacy.',
        ]);

        $patient = DB::table('users')->where('id', $patientId)->first();
        $addresses = [];
        foreach (['' => 'address', '2' => 'address2', '3' => 'address3'] as $suffix => $field) {
            $typed = trim($request->input($field).' '.$request->input('zip'.$suffix));
            $addresses[$suffix] = $request->filled($field) ? $geocoder->lookup($typed, $field) : null;
        }

        $primary = (int) $request->input('primary_address');
        if ($primary < 1 || $primary > 3 || ($primary > 1 && $addresses[(string) $primary] === null)) {
            $primary = 1;
        }

        $logChanges->handle($request, $addresses['']['formatted_address'], $patientId, $addresses['2']['formatted_address'] ?? null, $addresses['3']['formatted_address'] ?? null);

        $image = $this->storeUpload($request->file('image'), 'users') ?? $patient->image;
        if ($request->input('remove_photo') > 0) {
            $image = '/images/users/default-user-image.png';
        }

        $row = [
            ...Arr::only($validated, ['name', 'last_name', 'phone', 'zip', 'apartment', 'zip2', 'apartment2', 'zip3', 'apartment3']),
            'email' => $request->input('email') ?: 'patients'.((int) DB::table('users')->max('id') + 1).'@'.config('branding.account_email_domain'),
            'home_phone' => $request->input('home_phone') ?: null,
            'image' => $image,
            'primary_address' => $primary,
        ];
        foreach ($addresses as $suffix => $geo) {
            $row['address'.$suffix] = $geo['formatted_address'] ?? null;
            $row['location'.$suffix] = $geo['location'] ?? null;
        }
        DB::table('users')->where('id', $patientId)->update($row);
    }

    /**
     * @return array{0: Builder|\Illuminate\Database\Eloquent\Builder, 1: string, 2: int, 3: int}
     */
    private function searchAndPaginate(Request $request, Builder|\Illuminate\Database\Eloquent\Builder $query): array
    {
        $search = (string) $request->query('search', '');
        if ($search !== '') {
            $query->where(fn ($q) => $q
                ->where(DB::raw("CONCAT(name, ' ', last_name)"), 'LIKE', '%'.$search.'%')
                ->orWhere('email', 'LIKE', '%'.$search.'%')
                ->orWhere('phone', 'LIKE', '%'.$search.'%'));
        }
        $page = max(1, (int) $request->query('page', 1));

        return [$query, $search, $page, (int) ceil($query->count() / self::PER_PAGE)];
    }

    private function recentOrders(string $patientId)
    {
        $address = fn (string $column) => "case when users.primary_address=2 then users.{$column}2 when users.primary_address=3 then users.{$column}3 else users.{$column} end";

        return DB::table('orders')->where('user_id', $patientId)
            ->join('users', 'orders.user_id', '=', 'users.id')
            ->leftJoin('users as driver', 'orders.driver_id', '=', 'driver.id')
            ->join('statuses', 'orders.statuse_id', '=', 'statuses.id')
            ->leftJoin('statuses_copay', 'orders.statuse_copay', '=', 'statuses_copay.id')
            ->join('delivery_methods', 'orders.delivery_method_id', '=', 'delivery_methods.id')
            ->join('delivery_times', 'orders.delivery_time_id', '=', 'delivery_times.id')
            ->join('pharmacys', 'orders.pharmacy_id', '=', 'pharmacys.id')
            ->select('orders.id', 'orders.created', 'orders.driver_id', 'orders.merchantOrder', 'orders.special_instructions', 'orders.rating', 'orders.fridge', 'orders.actual', 'orders.eta', 'orders.facility',
                'driver.name as drivername', 'driver.last_name as driverlast_name', 'driver.pharmacy_id as driverpharmacy_id',
                'orders.count_bags', 'orders.signature', 'orders.statuse_id', 'orders.pharmacy_id', 'orders.copay',
                'users.name as username', 'delivery_methods.name as delivery_method', 'delivery_times.name as delivery_time', 'users.last_name as last_name',
                DB::raw($address('address').' as useraddress'), DB::raw($address('apartment').' as userapartment'),
                DB::raw($address('zip').' as userzip'), DB::raw($address('location').' as userlocation'),
                'users.os as useros', 'users.phone as userphone', 'pharmacys.name as pharmacyname', 'pharmacys.address as pharmacyaddress', 'pharmacys.phone as pharmacyphone',
                'orders.tariff', 'statuses.name as statusename', 'statuses.color as statusecolor', 'statuses_copay.name as statuse_copay_name', 'statuses_copay.color as statuse_copay_color')
            ->orderBy('orders.id', 'desc')->limit(8)->get();
    }

}
