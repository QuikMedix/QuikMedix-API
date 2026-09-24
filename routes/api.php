<?php

use App\Http\Controllers\Api\Auth\LoginController;
use App\Http\Controllers\Api\Auth\LogoutController;
use App\Http\Controllers\Api\Auth\RegisterController;
use App\Http\Controllers\BestRxApi;
use App\Http\Controllers\LexaAdminApi;
use App\Http\Controllers\LexaAdminApiNoAuth;
use App\Http\Controllers\MerchantApi;
use App\Http\Controllers\PioneerrxApi;
use App\Http\Controllers\TokenController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

//Route::middleware('auth:api')->get('/user', function (Request $request) {
    //return $request->user();
//});

Route::post('register', RegisterController::class);
Route::post('login', LoginController::class)->middleware('throttle:login');
Route::post('logout', LogoutController::class)->middleware('auth:api');

Route::get('/pharmacy-list', [LexaAdminApiNoAuth::class, 'pharmacyList']);
Route::post('/driver-create', [LexaAdminApiNoAuth::class, 'driversUsersAddHandler']);
Route::post('/chat-token', [TokenController::class, 'generate'])->name('token-generate');
Route::post('/users/get/', [LexaAdminApiNoAuth::class, 'getUserInfo']);
Route::post('/chats/new_message', [LexaAdminApiNoAuth::class, 'new_message']);
Route::post('/user/reset/', [LexaAdminApiNoAuth::class, 'reSendAuthMessage']);

Route::post('/telegram/auth/', [LexaAdminApiNoAuth::class, 'telegramAuth']);

Route::group(['prefix' => 'bestrx'], function () {
    Route::get('/Store', [BestRxApi::class, 'getpharmacyinfo']);
    Route::post('/Order/CreateOrder', [BestRxApi::class, 'orderAdd']);
    Route::post('/Address/ValidateAddress', [BestRxApi::class, 'validateAddress']);
    Route::post('/Order/CancelOrder', [BestRxApi::class, 'orderDelete']);
    Route::get('/orders/ticket', [BestRxApi::class, 'ordersTicket']);
    Route::get('/orders/ticket/print', [BestRxApi::class, 'ordersTicketPrintPdf']);
    Route::any('{any}', [BestRxApi::class, 'NotFound'])->where('any', '.*');
});

Route::group(['prefix' => 'merchant'], function () {
    Route::get('/getpharmacyinfo/{pharmacy_id}', [MerchantApi::class, 'getpharmacyinfo']);
    Route::post('/orders', [MerchantApi::class, 'orderAdd']);
    Route::post('/orders/status', [MerchantApi::class, 'orderStatus']);
    Route::post('/orders/{order_id}/prescriptions', [MerchantApi::class, 'orderRxAdd']);
    Route::delete('/orders/{order_id}/prescriptions/{rx_number}', [MerchantApi::class, 'orderRxDelete']);
    Route::delete('/orders/{order_id}', [MerchantApi::class, 'orderDelete']);
    Route::get('/orders/ticket', [MerchantApi::class, 'ordersTicket']);
    Route::get('/orders/ticket/print', [MerchantApi::class, 'ordersTicketPrintPdf']);
    Route::any('{any}', [MerchantApi::class, 'NotFound'])->where('any', '.*');
});

Route::group(['prefix' => 'enterprise'], function () {
    Route::post('/IsAuthenticated', [PioneerrxApi::class, 'IsAuthenticated']);
    Route::any('{any}', [PioneerrxApi::class, 'NotFound'])->where('any', '.*');
});

Route::middleware('auth:api')->group(function () {
    Route::get('/profile', [LexaAdminApi::class, 'profile']);
    Route::get('/banner', [LexaAdminApi::class, 'banner']);
    Route::post('/profile', [LexaAdminApi::class, 'profileHandler']);
    Route::get('/profile/family_members', [LexaAdminApi::class, 'profileFamily_members']);
    Route::post('/profile/family_members/add', [LexaAdminApi::class, 'profileFamily_membersAddHandler']);
    Route::post('/profile/family_members/remove', [LexaAdminApi::class, 'profileFamily_membersRemoveHandler']);
    Route::get('/patient/home', [LexaAdminApi::class, 'patient_home']);
    Route::get('/orders', [LexaAdminApi::class, 'ordersList']);
    Route::get('/orders/show/{order_id}', [LexaAdminApi::class, 'ordersShow']);
    Route::post('/orders/show/{order_id}', [LexaAdminApi::class, 'ordersShowHandler']);
    Route::get('/cards', [LexaAdminApi::class, 'cards']);
    Route::post('/cards/add', [LexaAdminApi::class, 'cardAddHandler']);
    Route::get('/routes', [LexaAdminApi::class, 'routes']);
    Route::get('/routes-logs', [LexaAdminApi::class, 'routesLogs']);
    Route::get('/routes/show/{route_id}', [LexaAdminApi::class, 'routesShow']);
    Route::get('/orders/delivery_times', [LexaAdminApi::class, 'ordersDelivery_times']);
    Route::post('/orders/delivery_times', [LexaAdminApi::class, 'ordersDelivery_timesHandler']);
    Route::post('/orders/qr_scaned', [LexaAdminApi::class, 'qr_scanedHandler']);
    Route::post('/orders/drop_off_qr', [LexaAdminApi::class, 'orderPatientQr']);
    Route::post('/orders/drop_off_qr2', [LexaAdminApi::class, 'orderPatientQr2']);
    Route::post('/orders/drop_off', [LexaAdminApi::class, 'drop_offHandler']);
    Route::post('/orders/signature', [LexaAdminApi::class, 'signatureHandler']);
    Route::post('/orders/update_status', [LexaAdminApi::class, 'update_status']);
    Route::post('/orders/{order_id}/add_note', [LexaAdminApi::class, 'customer_notesAddHandler']);
    Route::post('/orders/{order_id}/rating', [LexaAdminApi::class, 'ratingHandler']);
    Route::post('/orders/copay/pay/{order_id}', [LexaAdminApi::class, 'payCopay']);
    Route::post('/orders/copay/pay_cash/{order_id}', [LexaAdminApi::class, 'payedCashCopay']);
    Route::post('/orders/copay/not_pay/{order_id}', [LexaAdminApi::class, 'notPayedCashCopay']);
    Route::post('/driver/add_location', [LexaAdminApi::class, 'locationHandler']);
    Route::get('/driver/qr', [LexaAdminApi::class, 'driverQr']);
    Route::post('/update/device', [LexaAdminApi::class, 'updateDevice']);
    Route::get('/driver/status', [LexaAdminApi::class, 'driverStatus']);
    Route::get('/driver/status/start', [LexaAdminApi::class, 'driverStatusStart']);
    Route::get('/driver/status/finish', [LexaAdminApi::class, 'driverStatusFinish']);
    Route::get('/test/notification/{user_id}', [LexaAdminApi::class, 'testNotification']);
    Route::get('/user/countunread', [LexaAdminApi::class, 'userCountunread']);
    Route::get('/chats', [LexaAdminApi::class, 'chats']);
    Route::get('/chats/user/{user_id}', [LexaAdminApi::class, 'chatUser']);
    Route::get('/routes/start', [LexaAdminApi::class, 'startRoute']);
    Route::get('/user/count_noread', [LexaAdminApi::class, 'get_unread_mess']);
    Route::get('/driver/payouts', [LexaAdminApi::class, 'get_payouts_driver']);
    Route::get('/news', [LexaAdminApi::class, 'news']);
});
