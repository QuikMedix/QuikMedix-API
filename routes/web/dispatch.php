<?php

use App\Http\Controllers\LexaAdminApiNoAuth;
use App\Http\Controllers\DispatchingController;
use App\Http\Controllers\RouteOfferController;
use App\Http\Controllers\RoutePlanningController;
use App\Http\Controllers\RouteTemplatesController;
use Illuminate\Support\Facades\Route;

// Route planning, the dispatch board, route templates and route offers. Loaded by routes/web.php for signed-in, active accounts.

Route::get('/routes-list', [RoutePlanningController::class, 'orders'])->middleware('role:superadmin,admin,dispadmin,logist,medic');

Route::get('/routes-drivers', [RoutePlanningController::class, 'drivers'])->middleware('role:superadmin,admin,dispadmin,logist,medic');

Route::get('/routes-pharmacys', [RoutePlanningController::class, 'pharmacies'])->middleware('role:superadmin,admin,dispadmin,logist');

Route::get('/routes-list/show/{order_id}', [RoutePlanningController::class, 'showOrder']);
Route::post('/routes-list/show/{order_id}', [RoutePlanningController::class, 'assignOrder']);
// The assign-a-driver map asks for each driver's name; the page had been calling an address that did not exist.
Route::post('/users/get', [LexaAdminApiNoAuth::class, 'getUserInfo'])->middleware('role:superadmin,admin,dispadmin,logist,medic');

Route::get('/routes-list/driver/{driver_id}', [RoutePlanningController::class, 'showDriver']);
Route::post('/routes-list/driver/{driver_id}', [RoutePlanningController::class, 'updateDriver']);

Route::middleware('role:superadmin,admin,dispadmin,logist')->group(function () {
    Route::get('/dispatching', [DispatchingController::class, 'index']);
    Route::post('/dispatching', [DispatchingController::class, 'update']);
    Route::get('/dispatching/show/{driver_id}', [DispatchingController::class, 'show']);
    Route::post('/dispatching/show/{driver_id}', [DispatchingController::class, 'recalculateEta']);
});

Route::middleware('role:superadmin,admin,dispadmin,logist,medic')->group(function () {
    Route::resource('route-templates', RouteTemplatesController::class)->only(['index', 'store', 'show', 'destroy']);
    Route::post('route-templates/{id}/items', [RouteTemplatesController::class, 'updateItems']);
    Route::post('route-templates/{id}/assign', [RouteTemplatesController::class, 'assignToDriver']);
});

Route::middleware('role:superadmin,admin,dispadmin,logist')->group(function () {
    Route::get('/route-offers', [RouteOfferController::class, 'index']);
    Route::post('/route-offers', [RouteOfferController::class, 'store']);
    Route::post('/route-offers/{offer_id}/withdraw', [RouteOfferController::class, 'withdraw'])->whereNumber('offer_id');
});
