<?php

use App\Http\Controllers\Api\Auth\LoginController;
use App\Http\Controllers\Api\Auth\LogoutController;
use App\Http\Controllers\Api\Auth\RegisterController;
use App\Http\Controllers\Api\DeleteAccountController;
use App\Http\Controllers\Api\DeliveryTimeController;
use App\Http\Controllers\Api\DriverShiftController;
use App\Http\Controllers\Api\OrderFeedbackController;
use App\Http\Controllers\BestRxApi;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\DeliveryController;
use App\Http\Controllers\Api\RouteController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\ChatController;
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
| routes are loaded by bootstrap/app.php within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

//Route::middleware('auth:api')->get('/user', function (Request $request) {
    //return $request->user();
//});

Route::post('register', RegisterController::class);
Route::post('login', LoginController::class)->middleware('throttle:login');
Route::post('logout', LogoutController::class)->middleware('auth:api');
// Self-service account deletion; works even while the account is pending approval.
Route::post('account/delete', DeleteAccountController::class)->middleware('auth:api');

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

// Signed in with a token, on an account that is neither blocked nor waiting for approval.
Route::middleware(['auth:api', 'active.api'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'profile']);
    Route::get('/banner', [ProfileController::class, 'banner']);
    Route::post('/profile', [ProfileController::class, 'profileHandler']);
    Route::get('/profile/family_members', [ProfileController::class, 'profileFamily_members']);
    Route::post('/profile/family_members/add', [ProfileController::class, 'profileFamily_membersAddHandler']);
    Route::post('/profile/family_members/remove', [ProfileController::class, 'profileFamily_membersRemoveHandler']);
    Route::get('/patient/home', [ProfileController::class, 'patient_home']);
    Route::get('/orders', [OrderController::class, 'ordersList']);
    Route::get('/orders/show/{order_id}', [OrderController::class, 'ordersShow']);
    Route::post('/orders/show/{order_id}', [OrderController::class, 'ordersShowHandler']);
    Route::get('/cards', [PaymentController::class, 'cards']);
    Route::post('/cards/add', [PaymentController::class, 'cardAddHandler']);
    Route::get('/routes', [RouteController::class, 'routes']);
    Route::get('/routes-logs', [RouteController::class, 'routesLogs']);
    Route::get('/routes/show/{route_id}', [RouteController::class, 'routesShow']);
    Route::get('/orders/delivery_times', [DeliveryTimeController::class, 'index']);
    Route::post('/orders/delivery_times', [DeliveryTimeController::class, 'update']);
    Route::post('/orders/qr_scaned', [DeliveryController::class, 'qr_scanedHandler']);
    Route::post('/orders/drop_off_qr', [DeliveryController::class, 'orderPatientQr']);
    Route::post('/orders/drop_off_qr2', [DeliveryController::class, 'orderPatientQr2']);
    Route::post('/orders/drop_off', [DeliveryController::class, 'drop_offHandler']);
    Route::post('/orders/signature', [DeliveryController::class, 'signatureHandler']);
    Route::post('/orders/update_status', [OrderController::class, 'update_status']);
    Route::post('/orders/{order_id}/add_note', [OrderFeedbackController::class, 'storeNote']);
    Route::post('/orders/{order_id}/rating', [OrderFeedbackController::class, 'storeRating']);
    Route::post('/orders/copay/pay/{order_id}', [PaymentController::class, 'payCopay']);
    Route::post('/orders/copay/pay_cash/{order_id}', [PaymentController::class, 'PayedCashCopay']);
    Route::post('/orders/copay/not_pay/{order_id}', [PaymentController::class, 'notPayedCashCopay']);
    Route::post('/driver/add_location', [DriverShiftController::class, 'storeLocation']);
    Route::get('/driver/qr', [DriverShiftController::class, 'qr']);
    Route::post('/update/device', [ProfileController::class, 'updateDevice']);
    Route::get('/driver/status', [DriverShiftController::class, 'status']);
    Route::get('/driver/status/start', [DriverShiftController::class, 'start']);
    Route::get('/driver/status/finish', [DriverShiftController::class, 'finish']);
    Route::get('/test/notification/{user_id}', [ChatController::class, 'testNotification']);
    Route::get('/user/countunread', [ChatController::class, 'userCountunread']);
    Route::get('/chats', [ChatController::class, 'chats']);
    Route::get('/chats/user/{user_id}', [ChatController::class, 'chatUser']);
    Route::get('/routes/start', [RouteController::class, 'startRoute']);
    Route::get('/user/count_noread', [ChatController::class, 'get_unread_mess']);
    Route::get('/driver/payouts', [DriverShiftController::class, 'payouts']);
    Route::get('/news', [ProfileController::class, 'news']);
});
