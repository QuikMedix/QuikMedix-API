<?php

namespace App\Http\Controllers;

use App\Models\AdminArea;
use App\Models\Pharmacy;
use App\Models\ActionLog;
use App\Actions\LogUserChanges;
use App\Http\Controllers\Concerns\RendersPages;
use App\Support\PageLinks;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Twilio\Rest\Client;

/**
 * Accounts in settings: the lists of administrators, patients, pharmacy staff, drivers and
 * dispatchers with their bulk actions, and adding and editing an account. Formerly actions of
 * LexaAdmin, moved as they were.
 */
class UserSettingsController extends Controller
{
    use RendersPages;


    public function settingsAdmins() {
        $users = User::where('role','admin');
        if(!empty(request()->query('search'))) {
            $search = request()->query('search');
            $users = $users->where(function($query) use ($search) {
                $query->where(DB::raw("CONCAT(name, ' ', last_name)"),'LIKE','%'.$search.'%')
                      ->orWhere('email','LIKE','%'.$search.'%')
                      ->orWhere('phone','LIKE','%'.$search.'%');
                });
        } else {
            $search='';
        }
        if(!empty(request()->query('without_app'))) {
            $users = $users->whereNull('os')->whereDate('created_at', '>', date('Y-m-d', strtotime('now -7 day')));
        }
        $countOnPage=30;
        $max_pages=ceil($users->count()/$countOnPage);
        $page = $this->currentPage(request());
        $pages = PageLinks::around($page, $max_pages);
        $users = $users->offset(($page-1)*$countOnPage)->limit($countOnPage)->get();
        return view('settings.admins',['users'=>$users,'pages'=>$pages,'page0'=>$page,'search'=>$search,'title'=>'Settings Admins','br1'=>'Settings','br2'=>'Admins','alert'=>'']);
    }

    public function settingsAdminsHandler(Request $request) {
        if($request->input('activate')>0) {
            DB::table('users')->where('id', $request->input('user_id'))->update(['isactive' => 1]);
        }
        if($request->input('block')>0) {
            DB::table('users')->where('id', $request->input('user_id'))->update(['isblocked' => 1]);
        }
        if($request->input('unblock')>0) {
            DB::table('users')->where('id', $request->input('user_id'))->update(['isblocked' => 0]);
        }
        if($request->input('login')>0) {
            session(['login_superadmin' => Auth::user()->id]);
            Auth::loginUsingId($request->input('user_id'));
            return redirect('/');
        }
        return redirect('/settings/admins');
    }

    public function settingsUsers() {
        $users = User::where('role','!=','driver')->where('role','!=','logist')->where('role','!=','medic');
        if(!empty(request()->query('search'))) {
            $search = request()->query('search');
            $users = $users->where(function($query) use ($search) {
                $query->where(DB::raw("CONCAT(name, ' ', last_name)"),'LIKE','%'.$search.'%')
                      ->orWhere('email','LIKE','%'.$search.'%')
                      ->orWhere('phone','LIKE','%'.$search.'%');
                });
        } else {
            $search='';
        }
        if(!empty(request()->query('without_app'))) {
            $users = $users->whereNull('os')->whereDate('created_at', '>', date('Y-m-d', strtotime('now -7 day')));
        }
        $countOnPage=30;
        $max_pages=ceil($users->count()/$countOnPage);
        $page = $this->currentPage(request());
        $pages = PageLinks::around($page, $max_pages);
        $users = $users->offset(($page-1)*$countOnPage)->limit($countOnPage)->get();
        $res_view = view('settings.users',['users'=>$users,'pages'=>$pages,'page0'=>$page,'search'=>$search,'title'=>'Settings Users','br1'=>'Settings','br2'=>'Users','alert'=>'']);
        return $this->page(request(), $res_view);
    }

    public function settingsUsersHandler(Request $request) {
        $alert = $this->applyAccountAction($request);

        return $this->accountList(User::where('role','!=','driver')->where('role','!=','logist')->where('role','!=','medic'), 'settings.users', $alert);
    }

    public function settingsMedics() {
        $users = User::where('role','medic');
        if(!empty(request()->query('search'))) {
            $search = request()->query('search');
            $users = $users->where(function($query) use ($search) {
                $query->where(DB::raw("CONCAT(name, ' ', last_name)"),'LIKE','%'.$search.'%')
                      ->orWhere('email','LIKE','%'.$search.'%')
                      ->orWhere('phone','LIKE','%'.$search.'%');
                });
        } else {
            $search='';
        }
        $countOnPage=30;
        $max_pages=ceil($users->count()/$countOnPage);
        $page = $this->currentPage(request());
        $pages = PageLinks::around($page, $max_pages);
        $users = $users->offset(($page-1)*$countOnPage)->limit($countOnPage)->get();
        $res_view = view('settings.medics',['users'=>$users,'pages'=>$pages,'page0'=>$page,'search'=>$search,'title'=>'Settings Users','br1'=>'Settings','br2'=>'Users','alert'=>'']);
        return $this->page(request(), $res_view);
    }

    public function settingsMedicsHandler(Request $request) {
        $alert = $this->applyAccountAction($request);

        return $this->accountList(User::where('role','medic'), 'settings.medics', $alert);
    }

    public function settingsDrivers() {
        $users = User::where('role','driver');
        if(!empty(request()->query('search'))) {
            $search = request()->query('search');
            $users = $users->where(function($query) use ($search) {
                $query->where(DB::raw("CONCAT(name, ' ', last_name)"),'LIKE','%'.$search.'%')
                      ->orWhere('email','LIKE','%'.$search.'%')
                      ->orWhere('phone','LIKE','%'.$search.'%');
                });
        } else {
            $search='';
        }
        $countOnPage=30;
        $max_pages=ceil($users->count()/$countOnPage);
        $page = $this->currentPage(request());
        $pages = PageLinks::around($page, $max_pages);
        $users = $users->offset(($page-1)*$countOnPage)->limit($countOnPage)->get();
        $res_view = view('settings.drivers',['users'=>$users,'pages'=>$pages,'page0'=>$page,'search'=>$search,'title'=>'Settings Users','br1'=>'Settings','br2'=>'Users','alert'=>'']);
        return $this->page(request(), $res_view);
    }

    public function settingsDriversHandler(Request $request) {
        $alert = $this->applyAccountAction($request, true);

        return $this->accountList(User::where('role','driver'), 'settings.drivers', $alert);
    }

    public function settingsLogists() {
        $users = User::where('role','logist');
        if(!empty(request()->query('search'))) {
            $search = request()->query('search');
            $users = $users->where(function($query) use ($search) {
                $query->where(DB::raw("CONCAT(name, ' ', last_name)"),'LIKE','%'.$search.'%')
                      ->orWhere('email','LIKE','%'.$search.'%')
                      ->orWhere('phone','LIKE','%'.$search.'%');
                });
        } else {
            $search='';
        }
        $countOnPage=30;
        $max_pages=ceil($users->count()/$countOnPage);
        $page = $this->currentPage(request());
        $pages = PageLinks::around($page, $max_pages);
        $users = $users->offset(($page-1)*$countOnPage)->limit($countOnPage)->get();
        $res_view = view('settings.logists',['users'=>$users,'pages'=>$pages,'page0'=>$page,'search'=>$search,'title'=>'Settings Users','br1'=>'Settings','br2'=>'Users','alert'=>'']);
        return $this->page(request(), $res_view);
    }

    public function settingsLogistsHandler(Request $request) {
        $alert = $this->applyAccountAction($request);

        return $this->accountList(User::where('role','logist'), 'settings.logists', $alert);
    }

    /**
     * @param int|string $user_id
     */
    public function settingsUsersedit($user_id) {
        Gate::authorize('manage-account', [$user_id]);
        $user = DB::table('users')->where('id', $user_id)->first();
        $pharmacys = Pharmacy::query()->get();
        $user_actions = ActionLog::query()->where('user_id', $user_id)->get();
        $admin_areas = AdminArea::query()->get();
        $res_view = view('settings.user_edit',['user'=> $user,'admin_areas'=>$admin_areas, 'user_actions'=>$user_actions, 'pharmacys'=>$pharmacys, 'title'=>'User Edit','br1'=>'Settings','br2'=>'Users','br3'=>'User Edit','alert'=>'']);
        return $this->page(request(), $res_view);
    }

    /**
     * @param int|string $user_id
     */
    public function settingsUserseditHandler(Request $request,$user_id) {
        Gate::authorize('manage-account', [$user_id]);
        $user = DB::table('users')->where('id', $user_id)->first();
        if($request->filled('role') && $request->input('role') !== $user->role) {
            Gate::authorize('assign-role', $request->input('role'));
        }
        if($request->input('save')>0) {
            if($request->hasFile('image')) {
                $file = $request->file('image');
                $file->move(public_path() . '/images/users/',\App\Support\PublicUpload::name($request->file('image')));
                $src = '/images/users/'.\App\Support\PublicUpload::name($request->file('image'));
            } else {
                $src = $user->image;
            }
            if($request->input('remove_photo')>0) {
                $src = '/images/users/default-user-image.png';
            }
            if($request->hasFile('driving_license_img')) {
                $file = $request->file('driving_license_img');
                $file->move(public_path() . '/images/driving_license/',\App\Support\PublicUpload::name($request->file('driving_license_img')));
                $driving_license_img = '/images/driving_license/'.\App\Support\PublicUpload::name($request->file('driving_license_img'));
            } else {
                $driving_license_img = $user->driving_license_img;
            }
            if($request->hasFile('car_img')) {
                $file = $request->file('car_img');
                $file->move(public_path() . '/images/users/',\App\Support\PublicUpload::name($request->file('car_img')));
                $car_img = '/images/users/'.\App\Support\PublicUpload::name($request->file('car_img'));
            } else {
                $car_img = $user->car_img;
            }
            if(!empty($request->input('role')) && $request->input('role') !== $user->role) {
                $role = $request->input('role');
                ActionLog::query()->insert(['type'=>'change role ('.$role.')','user_id'=>$user_id,'action_user_id'=>Auth::user()->id]);
            } else {
                $role = $user->role;
            }
            $zone_id=NULL;
            if(!empty($request->input('zone_id'))) {
                $zone_id=$request->input('zone_id');
            }
            if($request->input('password')!='') {
                if($request->input('password')==$request->input('password2')) {
                    if(!empty($request->input('zip'))) {
                        $address = $request->input('address').' '.$request->input('zip');
                    } else {
                        $address = $request->input('address');
                    }
                    $data = app(\App\Actions\GeocodeAddress::class)->response($address);
                    $address = $data->results[0]->formatted_address;
                    $location = $data->results[0]->geometry->location->lat.','.$data->results[0]->geometry->location->lng;
                    app(LogUserChanges::class)->handle($request,$address,$user_id);
                    DB::table('users')->where('id', $user_id)->update(['name' => $request->input('name'),'last_name' => $request->input('last_name'),'email' => $request->input('email'),'phone' => $request->input('phone'), 'image' => $src,'address' => $address,'location' => $location,'password' => Hash::make($request->input('password')),'zip' => $request->input('zip'),'apartment' => $request->input('apartment'),'driving_license' => $request->input('driving_license'),'driving_license_img' => $driving_license_img,'identification_cards' => $request->input('identification_cards'),'transport' => $request->input('transport'), 'car_info' => $request->input('car_info'),'car_img' => $car_img,'payment_card' => $request->input('payment_card'), 'pharmacy_id' => $request->input('pharmacy'), 'role' => $role, 'zone_id'=>$zone_id]);
                    ActionLog::query()->insert(['type'=>'change password','user_id'=>$user_id,'action_user_id'=>Auth::user()->id]);
                } else {
                    $pharmacys = DB::table('pharmacys')->get();
                    $user = DB::table('users')->where('id', $user_id)->first();
                    $user_actions = DB::table('action_log')->where('user_id', $user_id)->get();
                    $admin_areas = DB::table('admin_areas')->get();
                    return view('settings.user_edit',['user'=> $user,'admin_areas'=>$admin_areas,'user_actions'=>$user_actions,'pharmacys'=>$pharmacys,'title'=>'User Edit','br1'=>'Settings','br2'=>'Users','br3'=>'User Edit','alert'=>'Passwords must match']);
                }
            } else {
                if(!empty($request->input('zip'))) {
                    $address = $request->input('address').' '.$request->input('zip');
                } else {
                    $address = $request->input('address');
                }
                $data = app(\App\Actions\GeocodeAddress::class)->response($address);
                $address = $data->results[0]->formatted_address;
                $location = $data->results[0]->geometry->location->lat.','.$data->results[0]->geometry->location->lng;
                app(LogUserChanges::class)->handle($request,$address,$user_id);
                DB::table('users')->where('id', $user_id)->update(['name' => $request->input('name'),'last_name' => $request->input('last_name'),'email' => $request->input('email'),'phone' => $request->input('phone'), 'image' => $src,'address' => $address,'location' => $location,'zip' => $request->input('zip'),'apartment' => $request->input('apartment'),'driving_license' => $request->input('driving_license'),'driving_license_img' => $driving_license_img,'identification_cards' => $request->input('identification_cards'),'transport' => $request->input('transport'), 'car_info' => $request->input('car_info'),'car_img' => $car_img,'payment_card' => $request->input('payment_card'), 'pharmacy_id' => $request->input('pharmacy'),'role' => $role, 'zone_id'=>$zone_id]);
            }
        }
        $pharmacys = DB::table('pharmacys')->get();
        $user = DB::table('users')->where('id', $user_id)->first();
        $user_actions = DB::table('action_log')->where('user_id', $user_id)->get();
        $admin_areas = DB::table('admin_areas')->get();
        return view('settings.user_edit',['user'=> $user,'admin_areas'=>$admin_areas,'user_actions'=>$user_actions,'pharmacys'=>$pharmacys,'title'=>'User Edit','br1'=>'Settings','br2'=>'Users','br3'=>'User Edit','alert'=>'']);
    }

    public function settingsUsersAdd() {
        $input['name']='';
        $input['last_name']='';
        $input['email']='';
        $input['phone']='';
        $input['image']='';
        $input['address']='';
        $input['password']='';
        $input['apartment']='';
        $input['zip']='';
        $input['pharmacy']='';
        $pharmacys = Pharmacy::query()->get();
        $res_view = view('settings.user_add',['pharmacys'=>$pharmacys,'title'=>'User Add','br1'=>'Settings','br2'=>'Users','br3'=>'User Add','alert'=>'','input'=>$input]);
        return $this->page(request(), $res_view);
    }

    public function settingsUsersAddHandler(Request $request) {
        if($request->input('save')>0) {
            if(!empty(DB::table('users')->where('email', $request->input('email'))->where('pharmacy_id', $request->input('pharmacy'))->first())) {
                $input['name']=$request->input('name');
                $input['last_name']=$request->input('last_name');
                $input['email']=$request->input('email');
                $input['phone']=$request->input('phone');
                $input['image']='';
                $input['address']=$request->input('address');
                $input['password']=$request->input('password');
                $input['zip']=$request->input('zip');
                $input['apartment']=$request->input('apartment');
                $input['pharmacy']=$request->input('pharmacy');
                $pharmacys = Pharmacy::query()->get();
                return view('settings.user_add',['pharmacys'=>$pharmacys,'title'=>'User Add','br1'=>'Settings','br2'=>'Users','br3'=>'User Add','alert'=>'User with this email already exists','input'=>$input]);
            } else {
                if(!empty(DB::table('users')->where('phone', $request->input('phone'))->where('pharmacy_id', $request->input('pharmacy'))->first())) {
                    $input['name']=$request->input('name');
                    $input['last_name']=$request->input('last_name');
                    $input['email']=$request->input('email');
                    $input['phone']=$request->input('phone');
                    $input['image']='';
                    $input['address']=$request->input('address');
                    $input['password']=$request->input('password');
                    $input['zip']=$request->input('zip');
                    $input['apartment']=$request->input('apartment');
                    $input['pharmacy']=$request->input('pharmacy');
                    $pharmacys = Pharmacy::query()->get();
                    return view('settings.user_add',['pharmacys'=>$pharmacys,'title'=>'User Add','br1'=>'Settings','br2'=>'Users','br3'=>'User Add','alert'=>'User with this phone already exists','input'=>$input]);
                } else {
                    if($request->hasFile('image')) {
                        $file = $request->file('image');
                        $file->move(public_path() . '/images/users/',\App\Support\PublicUpload::name($request->file('image')));
                        $src = '/images/users/'.\App\Support\PublicUpload::name($request->file('image'));
                    } else {
                        $src = '';
                    }
                    if(!empty($request->input('zip'))) {
                        $address = $request->input('address').' '.$request->input('zip');
                    } else {
                        $address = $request->input('address');
                    }
                    $data = app(\App\Actions\GeocodeAddress::class)->response($address);
                    $address = $data->results[0]->formatted_address;
                    $location = $data->results[0]->geometry->location->lat.','.$data->results[0]->geometry->location->lng;
                    DB::table('users')->insert(['name' => $request->input('name'),'last_name' => $request->input('last_name'),'email' => $request->input('email'),'phone' => $request->input('phone'),'image' => $src,'address' => $address,'location' => $location,'password' => Hash::make($request->input('password')),'zip' => $request->input('zip'),'apartment' => $request->input('apartment'),'pharmacy_id' => $request->input('pharmacy')]);
                }
            }
        }
        return redirect('settings/users');
    }

    /**
     * Carries out the button pressed on an accounts list: activate, block, unblock, remove or change role.
     *
     * @return string a message for the page, empty when there is nothing to say
     */
    private function applyAccountAction(Request $request, bool $welcomeByText = false): string
    {
        if($request->filled('user_id')) {
            Gate::authorize('manage-account', [$request->input('user_id')]);
        }
        // Each role change needs the right to hand out that role, not only the right to manage the account.
        foreach (['todriver' => 'driver', 'touser' => 'user', 'tologist' => 'logist', 'touseradmin' => 'admin', 'tomedic' => 'medic'] as $button => $role) {
            if($request->input($button)>0) {
                Gate::authorize('assign-role', $role);
            }
        }
        $alert='';
        if($request->input('activate')>0) {
            DB::table('users')->where('id', $request->input('user_id'))->update(['isactive' => 1]);
            if($welcomeByText) {
                $user = DB::table('users')->where('id', $request->input('user_id'))->first();
                try {
                    $twilio = new Client(config('app.twilio_sid'), config('app.twilio_auth_token'));
                    $twilio->messages->create("+1".str_replace(" ","",str_replace("-","",str_replace(")","",str_replace("(","",$user->phone)))), ["body" => "Welcome to QuikMedix, ".$user->name."!\nYour account has been verified. Now you can use the app.\nAll the best,\nThe team at QuikMedix", "from" => config('app.twilio_from_phone')]);
                } catch (\Throwable $exception) {
                    // The work around it continues, but the failure is no longer lost.
                    report($exception);
                }
            }
        }
        if($request->input('block')>0) {
            DB::table('users')->where('id', $request->input('user_id'))->update(['isblocked' => 1]);
        }
        if($request->input('unblock')>0) {
            DB::table('users')->where('id', $request->input('user_id'))->update(['isblocked' => 0]);
        }
        if($request->input('remove')>0) {
            User::query()->where('id', $request->input('user_id'))->delete();
        }
        if($request->input('todriver')>0) {
            DB::table('users')->where('id', $request->input('user_id'))->update(['role' => 'driver']);
        }
        if($request->input('touser')>0) {
            DB::table('users')->where('id', $request->input('user_id'))->update(['role' => 'user']);
        }
        if($request->input('tologist')>0) {
            DB::table('users')->where('id', $request->input('user_id'))->update(['role' => 'logist']);
        }
        if($request->input('touseradmin')>0) {
            DB::table('users')->where('id', $request->input('user_id'))->update(['role' => 'admin']);
        }
        if($request->input('tomedic')>0) {
            if((int) DB::table('users')->where('id', $request->input('user_id'))->value('pharmacy_id')>0) {
                DB::table('users')->where('id', $request->input('user_id'))->update(['role' => 'medic']);
            } else {
                $alert="Error, this user dont have pharmacy_id!";
            }
        }

        return $alert;
    }

    /**
     * The accounts list a handler shows again after its action, searched and paged.
     */
    private function accountList($users, string $view, string $alert)
    {
        if(!empty(request()->query('search'))) {
            $search = request()->query('search');
            $users = $users->where(function($query) use ($search) {
                $query->where(DB::raw("CONCAT(name, ' ', last_name)"),'LIKE','%'.$search.'%')
                      ->orWhere('email','LIKE','%'.$search.'%')
                      ->orWhere('phone','LIKE','%'.$search.'%');
                });
        } else {
            $search='';
        }
        $countOnPage=30;
        $max_pages=ceil($users->count()/$countOnPage);
        $page = $this->currentPage(request());
        $pages = PageLinks::around($page, $max_pages);
        $users = $users->offset(($page-1)*$countOnPage)->limit($countOnPage)->get();
        return view($view,['users'=>$users,'pages'=>$pages,'page0'=>$page,'search'=>$search,'title'=>'Users','br1'=>'Settings','br2'=>'Users','alert'=>$alert]);
        }
}
