<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\LexaAdmin;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('zadarma', 'LexaAdminApiNoAuth@zadarma_get');

Route::post('zadarma', 'LexaAdminApiNoAuth@zadarma_post');
Route::post('livetex_hook', 'LexaAdminApiNoAuth@livetex_hook');

Auth::routes();
Route::get('/logout', 'LexaAdmin@logout');
Route::get('2fa', [App\Http\Controllers\TwoFAController::class, 'index'])->name('2fa.index');
Route::post('2fa', [App\Http\Controllers\TwoFAController::class, 'store'])->name('2fa.post');
Route::get('2fa/reset', [App\Http\Controllers\TwoFAController::class, 'resend'])->name('2fa.resend');

// Render perticular view file by foldername and filename and all passed in only one controller at a time
//Route::get('{folder}/{file}', 'LexaAdmin@index');

// when render first time project redirect
Route::get('/home', 'LexaAdmin@home');

Route::get('/keep-live', "LexaAdmin@live");

// when render first time project redirect
Route::get('/', "LexaAdmin@root")->name('home');
Route::post('/back-to-superadmin-user', "LexaAdmin@back_to_superadmin_user")->name('back_to_superadmin_user');

Route::get('/pharmacy/{pharmacy_id}/users', "LexaAdmin@pharmacyUsers");
Route::post('/pharmacy/{pharmacy_id}/users', "LexaAdmin@pharmacyUsersHandler");

Route::get('/pharmacy/{pharmacy_id}/users/edit/{user_id}', "LexaAdmin@pharmacyUsersEdit");
Route::post('/pharmacy/{pharmacy_id}/users/edit/{user_id}', "LexaAdmin@pharmacyUsersEditHandler");

Route::get('/pharmacy/{pharmacy_id}/users/add', "LexaAdmin@pharmacyUsersAdd");
Route::post('/pharmacy/{pharmacy_id}/users/add', "LexaAdmin@pharmacyUsersAddHandler");

Route::get('/pharmacys', "LexaAdmin@pharmacysList");
Route::post('/pharmacys', "LexaAdmin@pharmacysListHandler");

Route::get('/pharmacys/integrations', "LexaAdmin@pharmacysIntegrations");
Route::post('/pharmacys/integrations', "LexaAdmin@pharmacysIntegrationsHandler");

Route::get('/pharmacys/edit/{pharmacy_id}', "LexaAdmin@pharmacysListEdit");
Route::post('/pharmacys/edit/{pharmacy_id}', "LexaAdmin@pharmacysListEditHandler");

Route::get('/pharmacys/tariff_map/{pharmacy_id}', "LexaAdmin@pharmacysTariffMap");

Route::get('/pharmacys/add', "LexaAdmin@pharmacysListAdd");
Route::post('/pharmacys/add', "LexaAdmin@pharmacysListAddHandler");

Route::get('/offices', "LexaAdmin@officesList");
Route::post('/offices', "LexaAdmin@officesListHandler");

Route::get('/offices/edit/{office_id}', "LexaAdmin@officesListEdit");
Route::post('/offices/edit/{office_id}', "LexaAdmin@officesListEditHandler");

Route::get('/offices/add', "LexaAdmin@officesListAdd");
Route::post('/offices/add', "LexaAdmin@officesListAddHandler");

Route::get('/drivers/{pharmacy_id}/users', "LexaAdmin@driversUsers");
Route::post('/drivers/{pharmacy_id}/users', "LexaAdmin@driversUsersHandler");

Route::get('/drivers/{pharmacy_id}/users/edit/{user_id}', "LexaAdmin@driversUsersEdit");
Route::post('/drivers/{pharmacy_id}/users/edit/{user_id}', "LexaAdmin@driversUsersEditHandler");

Route::get('/drivers/{pharmacy_id}/users/add', "LexaAdmin@driversUsersAdd");
Route::post('/drivers/{pharmacy_id}/users/add', "LexaAdmin@driversUsersAddHandler");

Route::get('/drivers/{driver_id}/profile', "LexaAdmin@driversProfile");
Route::post('/drivers/{driver_id}/profile', "LexaAdmin@driversProfileHandler");

Route::get('/drivers/{driver_id}/payouts', "LexaAdmin@driversPayouts");

Route::get('/patients/{pharmacy_id}', "LexaAdmin@patients");
Route::post('/patients/{pharmacy_id}', "LexaAdmin@patientsHandler");

Route::get('/facilitys/{pharmacy_id}', "LexaAdmin@facilitys");
Route::post('/facilitys/{pharmacy_id}', "LexaAdmin@facilitysHandler");

Route::get('/patients/{pharmacy_id}/add', "LexaAdmin@patientsAdd");
Route::post('/patients/{pharmacy_id}/add', "LexaAdmin@patientsAddHandler");

Route::get('/facilitys/{pharmacy_id}/add', "LexaAdmin@facilitysAdd");
Route::post('/facilitys/{pharmacy_id}/add', "LexaAdmin@facilitysAddHandler");

Route::get('/facilitys/{pharmacy_id}/edit/{user_id}', "LexaAdmin@facilitysEdit");
Route::post('/facilitys/{pharmacy_id}/edit/{user_id}', "LexaAdmin@facilitysEditHandler");

Route::get('/patients/{pharmacy_id}/import', "LexaAdmin@patientsImport");
Route::post('/patients/{pharmacy_id}/import', "LexaAdmin@patientsImportHandler");

Route::get('/patients/{pharmacy_id}/removed', "LexaAdmin@patientsRemoved");

Route::get('/patients/{pharmacy_id}/edit/{user_id}', "LexaAdmin@patientsEdit");
Route::post('/patients/{pharmacy_id}/edit/{user_id}', "LexaAdmin@patientsEditHandler");

Route::get('/routes-list', "LexaAdmin@routes");

Route::get('/routes-drivers', "LexaAdmin@routesDrivers");

Route::get('/routes-pharmacys', "LexaAdmin@routesPharmacys");

Route::post('/orders/{pharmacy_id}/ready', "LexaAdmin@ordersReadyHandler");

Route::get('/pay/copay/{order_id}', "LexaAdmin@payCopay");
Route::post('/pay/copay/{order_id}', "LexaAdmin@payCopayHandler");

Route::get('/routes-list/show/{order_id}', "LexaAdmin@routesShow");
Route::post('/routes-list/show/{order_id}', "LexaAdmin@routesShowHandler");

Route::get('/routes-list/driver/{driver_id}', "LexaAdmin@routesDriver");
Route::post('/routes-list/driver/{driver_id}', "LexaAdmin@routesDriverHandler");

Route::get('/orders/{pharmacy_id}', "LexaAdmin@orders");
Route::post('/orders/{pharmacy_id}', "LexaAdmin@ordersHandler");

Route::get('/search/json', "LexaAdmin@searchJson");

Route::get('/billing-old/{pharmacy_id}', "LexaAdmin@billing2");

Route::get('/billing/{pharmacy_id}', "LexaAdmin@billing");
Route::get('/billing/{pharmacy_id}/orders/{invoice_id}', "LexaAdmin@billingOrders");
Route::get('/billing/{pharmacy_id}/print/{invoice_id}', "LexaAdmin@billingPrint");
Route::get('/billing/{pharmacy_id}/print_report/{invoice_id}', "LexaAdmin@billingPrintReport");
Route::post('/billing/{pharmacy_id}', "LexaAdmin@billingHandler");

Route::get('/billing/{pharmacy_id}/invoice/add', "LexaAdmin@billingInvoiceAdd");
Route::post('/billing/{pharmacy_id}/invoice/add', "LexaAdmin@billingInvoiceAddHandler");

Route::get('/billing/{pharmacy_id}/invoice/{invoice_id}/edit', "LexaAdmin@billingInvoiceEdit");
Route::post('/billing/{pharmacy_id}/invoice/{invoice_id}/edit', "LexaAdmin@billingInvoiceEditHandler");

Route::get('/process/{pharmacy_id}', "LexaAdmin@process");
Route::get('/process/{pharmacy_id}/show/{driver_id}', "LexaAdmin@processShow");

Route::get('/import/{pharmacy_id}/order', "LexaAdmin@import_order");
Route::post('/import/{pharmacy_id}/order', "LexaAdmin@import_orderHandler");

Route::get('/orders', "LexaAdmin@ordersList");
Route::post('/orders', "LexaAdmin@ordersListHandler");

Route::get('/orders/day/print', "LexaAdmin@ordersDayPrint");
Route::get('/orders/ticket/print', "LexaAdmin@ordersTicketPrint");
Route::get('/orders/tickets/print', "LexaAdmin@ordersTicketsPrint");

Route::get('/orders/{pharmacy_id}/edit/{order_id}', "LexaAdmin@ordersEdit");
Route::post('/orders/{pharmacy_id}/edit/{order_id}', "LexaAdmin@ordersEditHandler");

Route::get('/orders/preview/{order_id}', "LexaAdmin@ordersPreview");

Route::get('/orders/{pharmacy_id}/facilitys_edit/{order_id}', "LexaAdmin@ordersFacilitysEdit");
Route::post('/orders/{pharmacy_id}/facilitys_edit/{order_id}', "LexaAdmin@ordersFacilitysEditHandler");

Route::get('/orders/{pharmacy_id}/show/{order_id}', "LexaAdmin@ordersShow");
Route::post('/orders/{pharmacy_id}/show/{order_id}', "LexaAdmin@ordersShowHandler");

Route::get('/orders/{pharmacy_id}/add', "LexaAdmin@ordersAdd");
Route::post('/orders/{pharmacy_id}/add', "LexaAdmin@ordersAddHandler");

Route::get('/orders/{pharmacy_id}/facilitys_add', "LexaAdmin@ordersFacilitysAdd");
Route::post('/orders/{pharmacy_id}/facilitys_add', "LexaAdmin@ordersFacilitysAddHandler");

Route::get('/orders/{pharmacy_id}/statistic', "LexaAdmin@ordersStatistic");
Route::post('/orders/{pharmacy_id}/statistic', "LexaAdmin@ordersStatisticHandler");

Route::get('/profile', "LexaAdmin@profile");
Route::post('/profile', "LexaAdmin@profileHandler");

Route::get('/settings/users', "LexaAdmin@settingsUsers");
Route::post('/settings/users', "LexaAdmin@settingsUsersHandler");

Route::get('/settings/admins', "LexaAdmin@settingsAdmins");
Route::post('/settings/admins', "LexaAdmin@settingsAdminsHandler");

Route::get('/settings/admin_areas', "LexaAdmin@settingsAdminAreas");
Route::get('/settings/admin_areas/add', "LexaAdmin@settingsAdminAreasAdd");
Route::post('/settings/admin_areas/add', "LexaAdmin@settingsAdminAreasAddHandler");
Route::get('/settings/admin_areas/edit/{admin_area_id}', "LexaAdmin@settingsAdminAreasEdit");
Route::post('/settings/admin_areas/edit/{admin_area_id}', "LexaAdmin@settingsAdminAreasEditHandler");

Route::get('/settings/medics', "LexaAdmin@settingsMedics");
Route::post('/settings/medics', "LexaAdmin@settingsMedicsHandler");

Route::get('/settings/drivers', "LexaAdmin@settingsDrivers");
Route::post('/settings/drivers', "LexaAdmin@settingsDriversHandler");

Route::get('/settings/logists', "LexaAdmin@settingsLogists");
Route::post('/settings/logists', "LexaAdmin@settingsLogistsHandler");

Route::get('/settings/users/edit/{user_id}', "LexaAdmin@settingsUsersedit");
Route::post('/settings/users/edit/{user_id}', "LexaAdmin@settingsUserseditHandler");

Route::get('/settings/users/add', "LexaAdmin@settingsUsersAdd");
Route::post('/settings/users/add', "LexaAdmin@settingsUsersAddHandler");

Route::get('/cards', "LexaAdmin@cardAdd");
Route::post('/cards', "LexaAdmin@cardAddHandler");

Route::get('/payment-method/{pharmacy_id}', "LexaAdmin@cardPharmacyAdd");
Route::post('/payment-method/{pharmacy_id}', "LexaAdmin@cardPharmacyAddHandler");

Route::get('/payment-method/{pharmacy_id}/refill', "LexaAdmin@refillPharmacyBalance");
Route::post('/payment-method/{pharmacy_id}/refill', "LexaAdmin@refillPharmacyBalanceHandler");

Route::get('/notifications', "LexaAdmin@notifications");

Route::post('/drivers/qr', 'LexaAdmin@driversQr');

Route::get('/drivers/{user_id}/packages', "LexaAdmin@driversPackages");
Route::post('/drivers/{user_id}/packages', "LexaAdmin@driversPackagesHandler");

Route::post('/drivers/qr_order', "LexaAdmin@driversQrOrder");

Route::post('/patients/{user_id}/resend', "LexaAdmin@reSendAuthMessage");

Route::get('/patients/{user_id}/family', "LexaAdmin@patients_family");
Route::get('/patients/{user_id}/additional_recipients', "LexaAdmin@patients_additional_recipients");

Route::get('/pusher/beams-auth', 'LexaAdminApiNoAuth@pusher_auth');

Route::get('/news', "LexaAdmin@news");
Route::get('/news/add', "LexaAdmin@newsAdd");
Route::post('/news/add', "LexaAdmin@newsAddHandler");

Route::get('/news_patient', "LexaAdmin@news_patient");
Route::get('/news_patient/add', "LexaAdmin@news_patientAdd");
Route::post('/news_patient/add', "LexaAdmin@news_patientAddHandler");

Route::get('/settings/wishes', "LexaAdmin@settingsWishesCategory");
Route::post('/settings/wishes', "LexaAdmin@settingsWishesCategoryHandler");

Route::get('/settings/wishes/add', "LexaAdmin@settingsWishesCategoryAdd");
Route::post('/settings/wishes/add', "LexaAdmin@settingsWishesCategoryAddHandler");

Route::get('/settings/area', "LexaAdmin@settingsStates");
Route::post('/settings/area', "LexaAdmin@settingsStatesHandler");

Route::get('/settings/area/add', "LexaAdmin@settingsStatesAdd");
Route::post('/settings/area/add', "LexaAdmin@settingsStatesAddHandler");

Route::get('/settings/area/{area_id}/edit', "LexaAdmin@settingsStatesEdit");
Route::post('/settings/area/{area_id}/edit', "LexaAdmin@settingsStatesEditHandler");

Route::get('/settings/wishes/{wish_id}/list', "LexaAdmin@settingsWishes");
Route::post('/settings/wishes/{wish_id}/list', "LexaAdmin@settingsWishesHandler");

Route::get('/settings/wishes/{wish_id}/add', "LexaAdmin@ssettingsWishesAdd");
Route::post('/settings/wishes/{wish_id}/add', "LexaAdmin@settingsWishesAddHandler");

Route::get('/settings/plans', "LexaAdmin@settingsPlans");
Route::post('/settings/plans', "LexaAdmin@settingsPlansHandler");

Route::get('/settings/plans/add', "LexaAdmin@settingsPlansAdd");
Route::post('/settings/plans/add', "LexaAdmin@settingsPlansAddHandler");

Route::get('/settings/plans/{plan_id}/edit', "LexaAdmin@settingsPlansEdit");
Route::post('/settings/plans/{plan_id}/edit', "LexaAdmin@settingsPlansEditHandler");

Route::get('/delivery-calendar', "LexaAdmin@deliveryCalendar");

Route::get('/ads', "LexaAdmin@ads");

Route::get('/payroll', "LexaAdmin@payroll");

Route::get('test', "LexaAdmin@test");

Route::post('/github_pull', 'LexaAdminApiNoAuth@github_pull');

Route::post('/github_pull_zoz', 'LexaAdminApiNoAuth@github_pull_zoz');

Route::get('/orders/get_records/{order_id}', "LexaAdmin@get_records");

Route::get('a2bchat', "LexaAdmin@a2bChat");

Route::get('/reports', "LexaAdmin@reports");
Route::get('/reports/billing', "LexaAdmin@reportsBilling");
Route::get('/reports/apps', "LexaAdmin@reportsApps");
Route::get('/reports/drivers', "LexaAdmin@reportsDrivers");
Route::get('/reports/pharmacies', "LexaAdmin@reportsPharmacies");
Route::get('/reports/invoices', "LexaAdmin@reportsInvoices");
Route::get('/reports/map', "LexaAdmin@reportsMap");
Route::post('/reports/map', "LexaAdmin@reportsMapHandler");
Route::get('/reports/customers', "LexaAdmin@reportsCustomers");
Route::get('/dispatching', "LexaAdmin@dispatching");
Route::post('/dispatching', "LexaAdmin@dispatchingHandler");
Route::get('/dispatching/show/{driver_id}', "LexaAdmin@dispatchingShow");
Route::post('/dispatching/show/{driver_id}', "LexaAdmin@dispatchingShowHandler");
Route::get('/faq', "LexaAdmin@faq");
Route::post('/faq', "LexaAdmin@faqHandler");
Route::get('/faq/add', "LexaAdmin@faqAdd");
Route::post('/faq/add', "LexaAdmin@faqAddHandler");

Route::get('/drivers', "LexaAdmin@drivers");

Route::post('/ready_call', "LexaAdmin@ready_call");

Route::get('/quickbook', "LexaAdmin@quickbook");
Route::get('/quickbook_callback', 'LexaAdmin@quickbookCallback');

Route::get('/feedback', "LexaAdmin@feedback");

Route::get('/happy-holidays', "LexaAdmin@happyHolidays");

// Route Templates
Route::resource('route-templates', 'RouteTemplatesController');
Route::post('route-templates/{id}/items', 'RouteTemplatesController@updateItems');
Route::post('route-templates/{id}/assign', 'RouteTemplatesController@assignToDriver');