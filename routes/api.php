<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LexaAdminApi;

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

Route::group(['namespace' => 'Api'], function () {
    Route::group(['namespace' => 'Auth'], function () {
        Route::post('register', 'RegisterController');
        Route::post('login', 'LoginController');
        Route::post('logout', 'LogoutController')->middleware('auth:api');
    });
});

Route::get('/pharmacy-list', 'LexaAdminApiNoAuth@pharmacyList');
Route::post('/driver-create', 'LexaAdminApiNoAuth@driversUsersAddHandler');
Route::post('/chat-token', ['uses' => 'TokenController@generate', 'as' => 'token-generate']);
Route::post('/users/get/', 'LexaAdminApiNoAuth@getUserInfo');
Route::post('/chats/new_message', 'LexaAdminApiNoAuth@new_message');
Route::post('/user/reset/', 'LexaAdminApiNoAuth@reSendAuthMessage');

Route::post('/telegram/auth/', 'LexaAdminApiNoAuth@telegramAuth');

Route::group(['prefix' => 'bestrx'], function () {
    Route::get('/Store', 'BestRxApi@getpharmacyinfo');
    Route::post('/Order/CreateOrder', 'BestRxApi@orderAdd');
    Route::post('/Address/ValidateAddress', 'BestRxApi@validateAddress');
    Route::post('/Order/CancelOrder', 'BestRxApi@orderDelete');
    Route::get('/orders/ticket', "BestRxApi@ordersTicket");
    Route::get('/orders/ticket/print', "BestRxApi@ordersTicketPrintPdf");
    Route::any('{any}', 'BestRxApi@NotFound')->where('any', '.*');
});

Route::group(['prefix' => 'merchant'], function () {
    Route::get('/getpharmacyinfo/{pharmacy_id}', 'MerchantApi@getpharmacyinfo');
    Route::post('/orders', 'MerchantApi@orderAdd');
    Route::post('/orders/status', 'MerchantApi@orderStatus');
    Route::post('/orders/{order_id}/prescriptions', 'MerchantApi@orderRxAdd');
    Route::delete('/orders/{order_id}/prescriptions/{rx_number}', 'MerchantApi@orderRxDelete');
    Route::delete('/orders/{order_id}', 'MerchantApi@orderDelete');
    Route::delete('/orders/{order_id}', 'MerchantApi@orderDelete');
    Route::get('/orders/ticket', "MerchantApi@ordersTicket");
    Route::get('/orders/ticket/print', "MerchantApi@ordersTicketPrintPdf");
    Route::any('{any}', 'MerchantApi@NotFound')->where('any', '.*');
});

Route::group(['prefix' => 'enterprise'], function () {
    Route::post('/IsAuthenticated', 'PioneerrxApi@IsAuthenticated');
    Route::any('{any}', 'PioneerrxApi@NotFound')->where('any', '.*');
});

Route::middleware('auth:api')->group(function () {
    Route::get('/profile', "LexaAdminApi@profile");
    Route::get('/banner', "LexaAdminApi@banner");
    Route::post('/profile', "LexaAdminApi@profileHandler");
    Route::get('/profile/family_members', "LexaAdminApi@profileFamily_members");
    Route::post('/profile/family_members/add', "LexaAdminApi@profileFamily_membersAddHandler");
    Route::post('/profile/family_members/remove', "LexaAdminApi@profileFamily_membersRemoveHandler");
    Route::get('/patient/home', "LexaAdminApi@patient_home");
    Route::get('/orders', "LexaAdminApi@ordersList");
    Route::get('/orders/show/{order_id}', "LexaAdminApi@ordersShow");
    Route::post('/orders/show/{order_id}', "LexaAdminApi@ordersShowHandler");
    Route::get('/cards', "LexaAdminApi@cards");
    Route::post('/cards/add', "LexaAdminApi@cardAddHandler");
    Route::get('/routes', "LexaAdminApi@routes");
    Route::get('/routes-logs', "LexaAdminApi@routesLogs");
    Route::get('/routes/show/{route_id}', "LexaAdminApi@routesShow");
    Route::get('/orders/delivery_times', "LexaAdminApi@ordersDelivery_times");
    Route::post('/orders/delivery_times', "LexaAdminApi@ordersDelivery_timesHandler");
    Route::post('/orders/qr_scaned', "LexaAdminApi@qr_scanedHandler");
    Route::post('/orders/drop_off_qr', "LexaAdminApi@orderPatientQr");
    Route::post('/orders/drop_off_qr2', "LexaAdminApi@orderPatientQr2");
    Route::post('/orders/drop_off', "LexaAdminApi@drop_offHandler");
    Route::post('/orders/signature', "LexaAdminApi@signatureHandler");
    Route::post('/orders/update_status', "LexaAdminApi@update_status");
    Route::post('/orders/{order_id}/add_note', "LexaAdminApi@customer_notesAddHandler");
    Route::post('/orders/{order_id}/rating', "LexaAdminApi@ratingHandler");
    Route::post('/orders/copay/pay/{order_id}', "LexaAdminApi@payCopay");
    Route::post('/orders/copay/pay_cash/{order_id}', "LexaAdminApi@payedCashCopay");
    Route::post('/orders/copay/not_pay/{order_id}', "LexaAdminApi@notPayedCashCopay");
    Route::post('/driver/add_location', "LexaAdminApi@locationHandler");
    Route::get('/driver/qr', "LexaAdminApi@driverQr");
    Route::post('/update/device', "LexaAdminApi@updateDevice");
    Route::get('/driver/status', "LexaAdminApi@driverStatus");
    Route::get('/driver/status/start', "LexaAdminApi@driverStatusStart");
    Route::get('/driver/status/finish', "LexaAdminApi@driverStatusFinish");
    Route::get('/test/notification/{user_id}', "LexaAdminApi@testNotification");
    Route::get('/user/countunread', "LexaAdminApi@userCountunread");
    Route::get('/chats', "LexaAdminApi@chats");
    Route::get('/chats/user/{user_id}', "LexaAdminApi@chatUser");
    Route::get('/routes/start', "LexaAdminApi@startRoute");
    Route::get('/user/count_noread', "LexaAdminApi@get_unread_mess");
    Route::get('/driver/payouts', "LexaAdminApi@get_payouts_driver");
    Route::get('/news', "LexaAdminApi@news");
});