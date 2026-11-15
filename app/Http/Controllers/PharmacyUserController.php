<?php

namespace App\Http\Controllers;

use App\Actions\LogUserChanges;
use App\Http\Controllers\Concerns\RendersPages;
use App\Support\PageLinks;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use App\Support\AccessMessages;

/**
 * The staff accounts of one pharmacy: list, add and edit. Formerly actions of LexaAdmin, moved as
 * they were.
 */
class PharmacyUserController extends Controller
{
    use RendersPages;


    /**
     * @param int|string $pharmacy_id
     */
    public function pharmacyUsers($pharmacy_id) {
        abort_unless(Auth::user()->can('manage-pharmacy', $pharmacy_id), 403, AccessMessages::NO_PERMISSION);
        $users = User::where('role','medic')->where('pharmacy_id',$pharmacy_id);
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
        $res_view = view('pharmacy.users',['pages'=>$pages,'page0'=>$page,'search'=>$search,'users'=>$users,'pharmacy_id'=>$pharmacy_id,'title'=>'Pharmacy Users','br1'=>'Pharmacy','br2'=>'Users']);
        return $this->page(request(), $res_view);
    }

    /**
     * @param int|string $pharmacy_id
     */
    public function pharmacyUsersHandler(Request $request, $pharmacy_id) {
        abort_unless(Auth::user()->can('manage-pharmacy', $pharmacy_id), 403, AccessMessages::NO_PERMISSION);
        if($request->filled('user_id')) {
            Gate::authorize('manage-account', [$request->input('user_id'), $pharmacy_id]);
        }
        if($request->input('activate')>0) {
            DB::table('users')->where('id', $request->input('user_id'))->update(['isactive' => 1]);
        }
        if($request->input('block')>0) {
            DB::table('users')->where('id', $request->input('user_id'))->update(['isblocked' => 1]);
        }
        if($request->input('unblock')>0) {
            DB::table('users')->where('id', $request->input('user_id'))->update(['isblocked' => 0]);
        }
        if($request->input('remove')>0) {
            DB::table('users')->where('id', $request->input('user_id'))->update(['pharmacy_id' => NULL]);
        }
        if($request->input('todriver')>0) {
            DB::table('users')->where('id', $request->input('user_id'))->update(['role' => 'driver']);
        }
        if($request->input('touser')>0) {
            DB::table('users')->where('id', $request->input('user_id'))->update(['role' => 'user']);
        }
        if($request->input('tomedic')>0) {
            DB::table('users')->where('id', $request->input('user_id'))->update(['role' => 'medic']);
        }
        $users = User::where('role','medic')->where('pharmacy_id',$pharmacy_id);
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
        return view('pharmacy.users',['pages'=>$pages,'page0'=>$page,'search'=>$search,'users'=>$users,'pharmacy_id'=>$pharmacy_id,'title'=>'Pharmacy Users','br1'=>'Pharmacy','br2'=>'Users']);
    }

    /**
     * @param int|string $pharmacy_id
     * @param int|string $user_id
     */
    public function pharmacyUsersEdit($pharmacy_id,$user_id) {
        abort_unless(Auth::user()->hasRole('medic') && Auth::user()->pharmacy_id==$pharmacy_id && User::query()->where('id', $user_id)->where('pharmacy_id', $pharmacy_id)->exists()  || (Auth::user()->can('admin')), 403, AccessMessages::NO_PERMISSION);
        $user = DB::table('users')->where('id', $user_id)->first();
        $user_actions = DB::table('action_log')->where('user_id', $user_id)->get();
        $pharmacys = DB::table('pharmacys')->get();
        $res_view = view('pharmacy.user_edit',['user'=> $user, 'user_actions'=>$user_actions, 'pharmacys'=>$pharmacys, 'title'=>'User Edit','br1'=>'Pharmacy','br2'=>'Users','br3'=>'User Edit','alert'=>'']);
        return $this->page(request(), $res_view);
    }

    /**
     * @param int|string $pharmacy_id
     * @param int|string $user_id
     */
    public function pharmacyUsersEditHandler(Request $request,$pharmacy_id,$user_id) {
        abort_unless(Auth::user()->can('manage-pharmacy', $pharmacy_id), 403, AccessMessages::NO_PERMISSION);
        Gate::authorize('manage-account', [$user_id, $pharmacy_id]);
        if($request->input('save')>0) {
            if($request->hasFile('image')) {
                $file = $request->file('image');
                $file->move(public_path() . '/images/users/',\App\Support\PublicUpload::name($request->file('image')));
                $src = '/images/users/'.\App\Support\PublicUpload::name($request->file('image'));
            } else {
                $user = DB::table('users')->where('id', $user_id)->first();
                $src = $user->image;
            }
            if($request->input('remove_photo')>0) {
                $src = '/images/users/default-user-image.png';
            }
            if(!empty($request->input('zip'))) {
                $address = $request->input('address').' '.$request->input('zip');
            } else {
                $address = $request->input('address');
            }
            $data = app(\App\Actions\GeocodeAddress::class)->response($address);
            $address = $data->results[0]->formatted_address;
            $location = $data->results[0]->geometry->location->lat.','.$data->results[0]->geometry->location->lng;
            app(LogUserChanges::class)->handle($request,$address,$user_id);
            DB::table('users')->where('id', $user_id)->update(['name' => $request->input('name'),'last_name' => $request->input('last_name'),'email' => $request->input('email'),'phone' => $request->input('phone'),'image' => $src,'address' => $address,'location' => $location,'zip' => $request->input('zip'),'apartment' => $request->input('apartment')]);
        }
        return redirect("pharmacy/$pharmacy_id/users");
    }

    /**
     * @param int|string $pharmacy_id
     */
    public function pharmacyUsersAdd($pharmacy_id) {
        abort_unless(Auth::user()->can('manage-pharmacy', $pharmacy_id), 403, AccessMessages::NO_PERMISSION);
        $input['name']='';
        $input['last_name']='';
        $input['email']='';
        $input['phone']='';
        $input['image']='';
        $input['address']='';
        $input['location']='';
        $input['password']='';
        $input['zip']='';
        $input['apartment']='';
        $input['pharmacy']=$pharmacy_id;
        $pharmacys = DB::table('pharmacys')->get();
        $res_view = view('pharmacy.user_add',['pharmacys'=>$pharmacys,'title'=>'User Add','br1'=>'Pharmacy','br2'=>'Users','br3'=>'User Add','alert'=>'','input'=>$input]);
        return $this->page(request(), $res_view);
    }

    /**
     * @param int|string $pharmacy_id
     */
    public function pharmacyUsersAddHandler(Request $request,$pharmacy_id) {
        abort_unless(Auth::user()->can('manage-pharmacy', $pharmacy_id), 403, AccessMessages::NO_PERMISSION);
        if($request->input('save')>0) {
            if(!empty(DB::table('users')->where('email', $request->input('email'))->first())) {
                $input['name']=$request->input('name');
                $input['last_name']=$request->input('name');
                $input['email']=$request->input('email');
                $input['phone']=$request->input('phone');
                $input['image']='';
                $input['address']=$request->input('address');
                $input['password']=$request->input('password');
                $input['zip']=$request->input('zip');
                $input['apartment']=$request->input('apartment');
                $input['pharmacy']=$pharmacy_id;
                $pharmacys = DB::table('pharmacys')->get();
                return view('pharmacy.user_add',['pharmacys'=>$pharmacys,'title'=>'User Add','br1'=>'Pharmacy','br2'=>'Users','br3'=>'User Add','alert'=>'User with this email already exists','input'=>$input]);
            } else {
                if(!empty(DB::table('users')->where('phone', $request->input('phone'))->first())) {
                    $input['name']=$request->input('name');
                    $input['last_name']=$request->input('name');
                    $input['email']=$request->input('email');
                    $input['phone']=$request->input('phone');
                    $input['image']='';
                    $input['address']=$request->input('address');
                    $input['password']=$request->input('password');
                    $input['zip']=$request->input('zip');
                    $input['apartment']=$request->input('apartment');
                    $input['pharmacy']=$pharmacy_id;
                    $pharmacys = DB::table('pharmacys')->get();
                    return view('pharmacy.user_add',['pharmacys'=>$pharmacys,'title'=>'User Add','br1'=>'Pharmacy','br2'=>'Users','br3'=>'User Add','alert'=>'User with this phone already exists','input'=>$input]);
                } else {
                    if($request->hasFile('image')) {
                        $file = $request->file('image');
                        $file->move(public_path() . '/images/users/',\App\Support\PublicUpload::name($request->file('image')));
                        $src = '/images/users/'.\App\Support\PublicUpload::name($request->file('image'));
                    } else {
                        $src = '';
                    }
                    $data = app(\App\Actions\GeocodeAddress::class)->response($request->input('address'));
                    $location = $data->results[0]->geometry->location->lat.','.$data->results[0]->geometry->location->lng;
                    DB::table('users')->insert(['isactive' => '1', 'role' => 'medic','name' => $request->input('name'),'last_name' => $request->input('last_name'),'email' => $request->input('email'),'phone' => $request->input('phone'),'image' => $src,'address' => $request->input('address'),'location' => $location,'password' => Hash::make($request->input('password')),'zip' => $request->input('zip'),'apartment' => $request->input('apartment'),'pharmacy_id' => $pharmacy_id]);
                }
            }
        }
        return redirect("pharmacy/$pharmacy_id/users");
    }
}
