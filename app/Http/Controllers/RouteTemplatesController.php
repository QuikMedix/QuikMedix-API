<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use App\Notifications; // Assuming this is used in LexaAdmin

class RouteTemplatesController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public static $err_act_ban = "We're sorry for the inconvenience, but it seems we have a little hiccup here. Our team is already on the case, working hard to get things back on track as quickly as possible.";
    public static $err_perm = 'Permision error.';

    /**
     * Display a listing of the route templates.
     */
    public function index()
    {
        if(Auth::user()->isblocked_or_isactive()) {
            return abort(403, self::$err_act_ban);
        }
        if((Auth::user()->role == 'superadmin' || Auth::user()->role == 'admin' || Auth::user()->role == 'dispadmin') || Auth::user()->role == 'logist' || Auth::user()->role == 'medic') {
            
            $templates = DB::table('route_templates')
                ->select('route_templates.*', DB::raw('(select count(*) from route_template_items where route_template_items.route_template_id = route_templates.id) as items_count'))
                ->orderBy('created_at', 'desc')
                ->paginate(20);

            return view('routes.templates.index', [
                'templates' => $templates,
                'title' => 'Route Templates',
                'br1' => 'Routes',
                'br2' => 'Templates'
            ]);
        } else {
            return abort(403, self::$err_perm);
        }
    }

    /**
     * Store a newly created route template in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $id = DB::table('route_templates')->insertGetId([
            'name' => $request->name,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect('/route-templates/' . $id);
    }

    /**
     * Delete a route template.
     */
    public function destroy($id)
    {
         if(Auth::user()->isblocked_or_isactive()) {
            return abort(403, self::$err_act_ban);
        }
        DB::table('route_templates')->where('id', $id)->delete();
        return redirect('/route-templates')->with('success', 'Template deleted successfully');
    }

    /**
     * Display the specified route template (Map Interface).
     */
    public function show($id)
    {
        if(Auth::user()->isblocked_or_isactive()) {
            return abort(403, self::$err_act_ban);
        }
        // Similar permissions as routesDriver
        if(((Auth::user()->role == 'superadmin' || Auth::user()->role == 'admin' || Auth::user()->role == 'dispadmin')) || (Auth::user()->role == 'logist') || (Auth::user()->role == 'medic')) {
            
            $template = DB::table('route_templates')->where('id', $id)->first();
            if(!$template) {
                return abort(404);
            }

            // Get items for this template
            $template_items = DB::table('route_template_items')
                ->where('route_template_id', $id)
                ->orderBy('priority', 'asc')
                ->get();

            // Prepare data for the view
            // We need lists of Pharmacies and Offices to show on the map/list
            
            $pharmacys = DB::table('pharmacys')->where("isactive",1)->where("isblocked",0)->get();
            $offices = DB::table('offices')->get();
            
            // For the map, we need locations of all potential points? 
            // Or just the points in the template + all pharmacies?
            // The user said "add points ... right click on map or drag".
            // If they can drag, we need a list.
            // If they can right click map, we need to know what is on the map.
            // Let's assume we show markers for all Pharmacies and Offices on the map.
            
            $pharmacy_locations = [];
            foreach($pharmacys as $pharmacy) {
                if(!empty($pharmacy->location)) {
                     $pharmacy_locations[] = [
                         'id' => $pharmacy->id, 
                         'location' => $pharmacy->location,
                         'name' => $pharmacy->name,
                         'address' => $pharmacy->address,
                         'type' => 'pharmacy'
                     ];
                }
            }

            // Also add offices to this map array if desired
             foreach($offices as $office) {
                 if(!empty($office->location)) {
                     $pharmacy_locations[] = [
                         'id' => $office->id, 
                         'location' => $office->location,
                         'name' => $office->name,
                         'address' => $office->location, // Office usually just has location geo
                         'type' => 'office'
                     ];
                 }
            }

            // Active Drivers for assignment popup
            $drivers = DB::table('users')->where('role', 'driver')->where('isactive', 1)->where('isblocked', 0)->select('id', 'name', 'last_name')->get();

            $res_view = view('routes.templates.show', [
                'template' => $template,
                'items' => $template_items,
                'pharmacys' => $pharmacys, 
                'offices' => $offices,
                'drivers' => $drivers,
                'pharmacy_locations' => $pharmacy_locations,
                'title' => 'Edit Route Template: ' . $template->name,
                'br1' => 'Routes',
                'br2' => 'Template Edit'
            ]);

            if(isset($_GET['ajax'])) {
                return $res_view->renderSections();
            } else {
                return $res_view;
            }

        } else {
            return abort(403, self::$err_perm);
        }
    }

    /**
     * Update the items in the template (Save Route).
     */
    public function updateItems(Request $request, $id)
    {
        // Expecting an array of items with type, type_id in order
        // items = [ {type: 'pharmacy', type_id: 1}, {type: 'office', type_id: 2}, ... ]
        
        $items = $request->input('items');
        
        DB::beginTransaction();
        try {
            DB::table('route_template_items')->where('route_template_id', $id)->delete();
            
            if(!empty($items)) {
                $insertData = [];
                $priority = 1;
                foreach($items as $item) {
                     $insertData[] = [
                         'route_template_id' => $id,
                         'type' => $item['type'],
                         'type_id' => $item['type_id'],
                         'priority' => $priority++,
                         'created_at' => now(),
                         'updated_at' => now()
                     ];
                }
                DB::table('route_template_items')->insert($insertData);
            }
            DB::commit();
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * Assign template to driver.
     */
    public function assignToDriver(Request $request, $id)
    {
        $driver_id = $request->input('driver_id');
        
        if(!$driver_id) {
            return redirect()->back()->with('error', 'Driver not selected');
        }

        $template_items = DB::table('route_template_items')
            ->where('route_template_id', $id)
            ->orderBy('priority', 'asc')
            ->get();
            
        if($template_items->isEmpty()) {
             return redirect()->back()->with('error', 'Template is empty');
        }

        // Logic:
        // Iterate through template points.
        // If Pharmacy: Find active orders for this Pharmacy.
        // If Patient: Find active orders for this Patient.
        // If Office: Add office point to route.
        // If no active orders for a point, SKIP it.
        // Add to routes_priority table for the driver.
        
        // Active orders definition: "statuse_id" in [1,2,3,7,8,9] (from routesDriver in LexaAdmin)
        // And usually Null driver_id or assigned to this driver? 
        // User said: "на актуальные заказы, если актуального заказа на эту точку нет, то пропускаем"
        
        // We will look for orders that don't have a driver yet or assigned to this driver?
        // Usually assigning a route implies taking unassigned orders.
        // Let's assume unassigned orders (driver_id IS NULL) or maybe we re-assign?
        // "assign order... to this driver" implies update driver_id.
        
        // Let's look for orders with status [1,2,3,7,8,9]
        // Filter by pharmacy_id (if point is pharmacy) or user_id (if point is patient)
        
        $count_priority = DB::table('routes_priority')->where('driver_id', $driver_id)->orderBy("priority","desc")->first();
        $current_priority = empty($count_priority) ? 0 : $count_priority->priority;
        
        $data_array = [];
        $added_count = 0;
        
        // First pass: Collect all orders for this template
        $all_orders = [];
        // We need to map which orders belong to which item (pharmacy) to avoid re-querying
        $orders_by_item = [];

        foreach($template_items as $index => $item) {
            if ($item->type == 'pharmacy') {
                $orders = DB::table('orders')
                    ->where('pharmacy_id', $item->type_id)
                    ->whereIn('statuse_id', [1,2,3,7,8,9]) // Active statuses
                    ->whereNull('driver_id')
                    ->get();
                
                if($orders->isNotEmpty()) {
                    $orders_by_item[$index] = $orders;
                    foreach($orders as $order) {
                        $all_orders[] = $order->id;
                    }
                }
            }
        }

        // Second pass: Build route priority
        foreach($template_items as $index => $item) {
            if($item->type == 'office') {
                // Add an entry for EVERY order in this template
                if(!empty($all_orders)) {
                    foreach($all_orders as $oid) {
                        $current_priority++;
                        $data_array[] = [
                            'driver_id' => $driver_id,
                            'order_id' => $oid, 
                            'type' => 'office',
                            'type_id' => $item->type_id,
                            'priority' => $current_priority,
                        ];
                    }
                    $added_count++; 
                }
                
            } elseif ($item->type == 'pharmacy') {
                if(isset($orders_by_item[$index])) {
                    $orders = $orders_by_item[$index];
                    foreach($orders as $order) {
                        $current_priority++;
                        $data_array[] = [
                            'driver_id' => $driver_id,
                            'order_id' => $order->id,
                            'type' => 'pharmacy',
                            'type_id' => $item->type_id,
                            'priority' => $current_priority,
                        ];
                        
                        // Update order to assigned
                         DB::table('orders')->where('id', $order->id)->update([
                             'driver_id' => $driver_id,
                             'statuse_id' => 2,
                             'ready' => 2 
                         ]);
                    }
                    $added_count++;
                }
            } elseif ($item->type == 'patient') {
                // Future implementation
            }
        }
        
        if(!empty($data_array)) {
            DB::table('routes_priority')->insert($data_array);
            Notifications::send_push($driver_id,"A2BRx","New route template assigned to you.");
            return redirect()->back()->with('success', "Template assigned to driver! Added $added_count points (some may contain multiple orders).");
        } else {
             return redirect()->back()->with('error', 'No relevant active orders found for the points in this template.');
        }
    }
}
