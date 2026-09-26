<?php

namespace App\Http\Controllers;

use App\Actions\GeocodeAddress;
use App\Actions\LogUserChanges;
use App\Http\Controllers\Concerns\RendersLegacyPages;
use App\Http\Requests\PharmacyDriverRequest;
use App\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

/**
 * A pharmacy's own drivers: list, add and edit. Formerly the driversUsers* actions of LexaAdmin.
 */
class PharmacyDriverController extends Controller
{
    use RendersLegacyPages;

    private const PER_PAGE = 30;

    /** @var list<string> */
    private const PROFILE_FIELDS = ['name', 'last_name', 'email', 'phone', 'zip', 'apartment', 'driving_license', 'identification_cards', 'transport', 'car_info', 'payment_card'];

    public function __construct()
    {
        $this->middleware(['auth', '2fa', 'active']);
    }

    public function index(Request $request, string $pharmacy_id): View|array
    {
        $this->authorize('manage-pharmacy', $pharmacy_id);

        // type[]=1: this pharmacy's drivers (default), type[]=2: drivers without a pharmacy.
        $users = User::where('role', 'driver');
        match ($request->query('type')) {
            ['1', '2'] => $users->where(fn ($query) => $query->where('pharmacy_id', $pharmacy_id)->orWhereNull('pharmacy_id')->orWhere('pharmacy_id', '')),
            ['2'] => $users->where(fn ($query) => $query->whereNull('pharmacy_id')->orWhere('pharmacy_id', '')),
            default => $users->where('pharmacy_id', $pharmacy_id),
        };

        $search = (string) $request->query('search', '');
        if ($search !== '') {
            $users->where(fn ($query) => $query
                ->where(DB::raw("CONCAT(name, ' ', last_name)"), 'LIKE', '%'.$search.'%')
                ->orWhere('email', 'LIKE', '%'.$search.'%')
                ->orWhere('phone', 'LIKE', '%'.$search.'%'));
        }

        $page = max(1, (int) $request->query('page', 1));
        $maxPages = (int) ceil($users->count() / self::PER_PAGE);
        $users = $users->offset(($page - 1) * self::PER_PAGE)->limit(self::PER_PAGE)->get();
        foreach ($users as $user) {
            $location = DB::table('locations')->where('user_id', $user->id)->orderBy('id', 'desc')->value('location');
            if ($location !== null) {
                $user->location = $location;
            }
        }

        return $this->page($request, view('drivers.users', [
            'pages' => $this->pageLinks($page, $maxPages),
            'page0' => $page,
            'search' => $search,
            'pharmacylocation' => DB::table('pharmacys')->where('id', $pharmacy_id)->value('location') ?? '',
            'users' => $users,
            'pharmacy_id' => $pharmacy_id,
            'title' => 'Drivers Users', 'br1' => 'Drivers', 'br2' => 'Users',
        ]));
    }

    /**
     * Activate (approve), block, unblock or remove one of this pharmacy's drivers from the list.
     * A removed driver goes back to pending, so leaving a pharmacy never makes them an active courier.
     */
    public function updateStatus(Request $request, string $pharmacy_id): RedirectResponse
    {
        $driverId = $request->input('user_id');
        $this->authorize('manage-pharmacy-driver', [$pharmacy_id, $driverId]);

        $changes = array_filter([
            'isactive' => $request->input('activate') > 0 ? 1 : null,
            'isblocked' => $request->input('block') > 0 ? 1 : ($request->input('unblock') > 0 ? 0 : null),
        ], fn ($value) => $value !== null);
        if ($request->input('remove') > 0) {
            $changes['pharmacy_id'] = null;
            $changes['isactive'] = 0;
        }
        if ($changes !== []) {
            DB::table('users')->where('id', $driverId)->update($changes);
        }

        return redirect("drivers/$pharmacy_id/users")->with('success', match (true) {
            $request->input('remove') > 0 => 'Driver removed.',
            $request->input('activate') > 0 => 'Driver approved.',
            default => 'Driver updated.',
        });
    }

    public function create(Request $request, string $pharmacy_id): View|array
    {
        $this->authorize('manage-pharmacy', $pharmacy_id);

        $input = array_fill_keys([...self::PROFILE_FIELDS, 'address', 'password', 'image', 'driving_license_img', 'car_img'], '');
        $input = array_merge($input, array_intersect_key($request->old(), $input), ['password' => '', 'pharmacy' => $pharmacy_id]);

        return $this->page($request, view('drivers.user_add', [
            'pharmacys' => DB::table('pharmacys')->get(),
            'title' => 'Drivers Add', 'br1' => 'Drivers', 'br2' => 'Users', 'br3' => 'Driver Add',
            'alert' => '',
            'input' => $input,
        ]));
    }

    public function store(PharmacyDriverRequest $request, string $pharmacy_id, GeocodeAddress $geocoder): RedirectResponse
    {
        $location = $geocoder->handle($request->input('address'));

        DB::table('users')->insert([
            ...$request->safe()->only(self::PROFILE_FIELDS),
            'isactive' => '1',
            'role' => 'driver',
            'address' => $request->input('address'),
            'location' => $location,
            'password' => Hash::make($request->input('password')),
            'image' => $this->storeUpload($request->file('image'), 'users') ?? '',
            'driving_license_img' => $this->storeUpload($request->file('driving_license_img'), 'driving_license') ?? '',
            'car_img' => $this->storeUpload($request->file('car_img'), 'users') ?? '',
            'pharmacy_id' => $pharmacy_id,
        ]);

        return redirect("drivers/$pharmacy_id/users")->with('success', 'Driver added.');
    }

    public function edit(Request $request, string $pharmacy_id, string $user_id): View|array
    {
        $this->authorize('manage-pharmacy-driver', [$pharmacy_id, $user_id]);

        return $this->page($request, $this->editView($user_id));
    }

    public function update(PharmacyDriverRequest $request, string $pharmacy_id, string $user_id, GeocodeAddress $geocoder, LogUserChanges $logChanges): RedirectResponse
    {
        $driver = DB::table('users')->where('id', $user_id)->first();
        $geo = $geocoder->lookup(trim($request->input('address').' '.$request->input('zip')));
        $logChanges->handle($request, $geo['formatted_address'], $user_id);

        $image = $this->storeUpload($request->file('image'), 'users') ?? $driver->image;
        if ($request->input('remove_photo') > 0) {
            $image = '/images/users/default-user-image.png';
        }

        DB::table('users')->where('id', $user_id)->update([
            ...$request->safe()->only(self::PROFILE_FIELDS),
            'address' => $geo['formatted_address'],
            'location' => $geo['location'],
            'image' => $image,
            'driving_license_img' => $this->storeUpload($request->file('driving_license_img'), 'driving_license') ?? $driver->driving_license_img,
            'car_img' => $this->storeUpload($request->file('car_img'), 'users') ?? $driver->car_img,
        ]);

        return redirect("drivers/$pharmacy_id/users/edit/$user_id")->with('success', 'Driver saved.');
    }

    private function editView(string $driverId): View
    {
        return view('drivers.user_edit', [
            'user' => DB::table('users')->where('id', $driverId)->first(),
            'user_actions' => DB::table('action_log')->where('user_id', $driverId)->get(),
            'pharmacys' => DB::table('pharmacys')->get(),
            'title' => 'Drivers Edit', 'br1' => 'Drivers', 'br2' => 'Users', 'br3' => 'Driver Edit',
            'alert' => '',
        ]);
    }
}
