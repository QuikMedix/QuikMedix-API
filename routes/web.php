<?php

use App\Http\Controllers\LexaAdmin;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\FacilityOrderController;
use App\Http\Controllers\OrderPrintController;
use App\Http\Controllers\AllOrdersController;
use App\Http\Controllers\LexaAdminApiNoAuth;
use App\Http\Controllers\FacilityController;
use App\Http\Controllers\OrderCreationController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\PharmacyDriverController;
use App\Http\Controllers\RouteTemplatesController;
use App\Http\Controllers\TwoFAController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

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

Route::get('zadarma', [LexaAdminApiNoAuth::class, 'zadarma_get']);

Route::post('zadarma', [LexaAdminApiNoAuth::class, 'zadarma_post']);

Auth::routes();
Route::get('/logout', [LexaAdmin::class, 'logout']);
Route::get('2fa', [TwoFAController::class, 'index'])->name('2fa.index');
Route::post('2fa', [TwoFAController::class, 'store'])->name('2fa.post');
Route::get('2fa/reset', [TwoFAController::class, 'resend'])->name('2fa.resend');

// Render perticular view file by foldername and filename and all passed in only one controller at a time
//Route::get('{folder}/{file}', [LexaAdmin::class, 'index']);

// when render first time project redirect
Route::get('/home', [LexaAdmin::class, 'home']);

Route::get('/keep-live', [LexaAdmin::class, 'live']);

// when render first time project redirect
Route::get('/', [LexaAdmin::class, 'root'])->name('home');
Route::post('/back-to-superadmin-user', [LexaAdmin::class, 'back_to_superadmin_user'])->name('back_to_superadmin_user');

Route::get('/pharmacy/{pharmacy_id}/users', [LexaAdmin::class, 'pharmacyUsers']);
Route::post('/pharmacy/{pharmacy_id}/users', [LexaAdmin::class, 'pharmacyUsersHandler']);

Route::get('/pharmacy/{pharmacy_id}/users/edit/{user_id}', [LexaAdmin::class, 'pharmacyUsersEdit']);
Route::post('/pharmacy/{pharmacy_id}/users/edit/{user_id}', [LexaAdmin::class, 'pharmacyUsersEditHandler']);

Route::get('/pharmacy/{pharmacy_id}/users/add', [LexaAdmin::class, 'pharmacyUsersAdd']);
Route::post('/pharmacy/{pharmacy_id}/users/add', [LexaAdmin::class, 'pharmacyUsersAddHandler']);

Route::get('/pharmacys', [LexaAdmin::class, 'pharmacysList']);
Route::post('/pharmacys', [LexaAdmin::class, 'pharmacysListHandler']);

Route::get('/pharmacys/integrations', [LexaAdmin::class, 'pharmacysIntegrations']);
Route::post('/pharmacys/integrations', [LexaAdmin::class, 'pharmacysIntegrationsHandler']);

Route::get('/pharmacys/edit/{pharmacy_id}', [LexaAdmin::class, 'pharmacysListEdit']);
Route::post('/pharmacys/edit/{pharmacy_id}', [LexaAdmin::class, 'pharmacysListEditHandler']);

Route::get('/pharmacys/tariff_map/{pharmacy_id}', [LexaAdmin::class, 'pharmacysTariffMap']);

Route::get('/pharmacys/add', [LexaAdmin::class, 'pharmacysListAdd']);
Route::post('/pharmacys/add', [LexaAdmin::class, 'pharmacysListAddHandler']);

Route::get('/offices', [LexaAdmin::class, 'officesList']);
Route::post('/offices', [LexaAdmin::class, 'officesListHandler']);

Route::get('/offices/edit/{office_id}', [LexaAdmin::class, 'officesListEdit']);
Route::post('/offices/edit/{office_id}', [LexaAdmin::class, 'officesListEditHandler']);

Route::get('/offices/add', [LexaAdmin::class, 'officesListAdd']);
Route::post('/offices/add', [LexaAdmin::class, 'officesListAddHandler']);

Route::get('/drivers/{pharmacy_id}/users', [PharmacyDriverController::class, 'index']);
Route::post('/drivers/{pharmacy_id}/users', [PharmacyDriverController::class, 'updateStatus']);

Route::get('/drivers/{pharmacy_id}/users/edit/{user_id}', [PharmacyDriverController::class, 'edit']);
Route::post('/drivers/{pharmacy_id}/users/edit/{user_id}', [PharmacyDriverController::class, 'update']);

Route::get('/drivers/{pharmacy_id}/users/add', [PharmacyDriverController::class, 'create']);
Route::post('/drivers/{pharmacy_id}/users/add', [PharmacyDriverController::class, 'store']);

Route::get('/drivers/{driver_id}/profile', [LexaAdmin::class, 'driversProfile']);
Route::post('/drivers/{driver_id}/profile', [LexaAdmin::class, 'driversProfileHandler']);

Route::get('/drivers/{driver_id}/payouts', [LexaAdmin::class, 'driversPayouts']);

Route::get('/patients/{pharmacy_id}', [PatientController::class, 'index']);
Route::post('/patients/{pharmacy_id}', [PatientController::class, 'updateStatus']);

Route::get('/facilitys/{pharmacy_id}', [FacilityController::class, 'index']);
Route::post('/facilitys/{pharmacy_id}', [FacilityController::class, 'updateStatus']);

Route::get('/patients/{pharmacy_id}/add', [PatientController::class, 'create']);
Route::post('/patients/{pharmacy_id}/add', [PatientController::class, 'store']);

Route::get('/facilitys/{pharmacy_id}/add', [FacilityController::class, 'create']);
Route::post('/facilitys/{pharmacy_id}/add', [FacilityController::class, 'store']);

Route::get('/facilitys/{pharmacy_id}/edit/{user_id}', [FacilityController::class, 'edit']);
Route::post('/facilitys/{pharmacy_id}/edit/{user_id}', [FacilityController::class, 'update']);

Route::get('/patients/{pharmacy_id}/import', [LexaAdmin::class, 'patientsImport']);
Route::post('/patients/{pharmacy_id}/import', [LexaAdmin::class, 'patientsImportHandler']);

Route::get('/patients/{pharmacy_id}/removed', [PatientController::class, 'removed']);

Route::get('/patients/{pharmacy_id}/edit/{user_id}', [PatientController::class, 'edit']);
Route::post('/patients/{pharmacy_id}/edit/{user_id}', [PatientController::class, 'update']);

Route::get('/routes-list', [LexaAdmin::class, 'routes']);

Route::get('/routes-drivers', [LexaAdmin::class, 'routesDrivers']);

Route::get('/routes-pharmacys', [LexaAdmin::class, 'routesPharmacys']);

Route::post('/orders/{pharmacy_id}/ready', [OrderController::class, 'markReady']);

Route::get('/pay/copay/{order_id}', [LexaAdmin::class, 'payCopay']);
Route::post('/pay/copay/{order_id}', [LexaAdmin::class, 'payCopayHandler']);

Route::get('/routes-list/show/{order_id}', [LexaAdmin::class, 'routesShow']);
Route::post('/routes-list/show/{order_id}', [LexaAdmin::class, 'routesShowHandler']);

Route::get('/routes-list/driver/{driver_id}', [LexaAdmin::class, 'routesDriver']);
Route::post('/routes-list/driver/{driver_id}', [LexaAdmin::class, 'routesDriverHandler']);

// Global creation links need a pharmacy before opening the scoped form.
Route::get('/orders/add', [OrderCreationController::class, 'create'])->name('orders.create');
Route::get('/orders/facilitys_add', [OrderCreationController::class, 'createFacility'])->name('orders.facility.create');

Route::get('/orders/{pharmacy_id}', [OrderController::class, 'index'])->whereNumber('pharmacy_id');
Route::post('/orders/{pharmacy_id}', [OrderController::class, 'updateStatus'])->whereNumber('pharmacy_id');

Route::get('/search/json', [LexaAdmin::class, 'searchJson']);

Route::get('/billing-old/{pharmacy_id}', [LexaAdmin::class, 'billing2']);

Route::get('/billing/{pharmacy_id}', [LexaAdmin::class, 'billing']);
Route::get('/billing/{pharmacy_id}/orders/{invoice_id}', [LexaAdmin::class, 'billingOrders']);
Route::get('/billing/{pharmacy_id}/print/{invoice_id}', [LexaAdmin::class, 'billingPrint']);
Route::get('/billing/{pharmacy_id}/print_report/{invoice_id}', [LexaAdmin::class, 'billingPrintReport']);
Route::post('/billing/{pharmacy_id}', [LexaAdmin::class, 'billingHandler']);

Route::get('/billing/{pharmacy_id}/invoice/add', [LexaAdmin::class, 'billingInvoiceAdd']);
Route::post('/billing/{pharmacy_id}/invoice/add', [LexaAdmin::class, 'billingInvoiceAddHandler']);

Route::get('/billing/{pharmacy_id}/invoice/{invoice_id}/edit', [LexaAdmin::class, 'billingInvoiceEdit']);
Route::post('/billing/{pharmacy_id}/invoice/{invoice_id}/edit', [LexaAdmin::class, 'billingInvoiceEditHandler']);

Route::get('/process/{pharmacy_id}', [LexaAdmin::class, 'process']);
Route::get('/process/{pharmacy_id}/show/{driver_id}', [LexaAdmin::class, 'processShow']);

Route::get('/import/{pharmacy_id}/order', [LexaAdmin::class, 'import_order']);
Route::post('/import/{pharmacy_id}/order', [LexaAdmin::class, 'import_orderHandler']);

Route::get('/orders', [AllOrdersController::class, 'index']);
Route::post('/orders', [AllOrdersController::class, 'updateStatus']);

Route::get('/orders/day/print', [OrderPrintController::class, 'day']);
Route::get('/orders/ticket/print', [OrderPrintController::class, 'ticket']);
Route::get('/orders/tickets/print', [OrderPrintController::class, 'tickets']);

Route::get('/orders/{pharmacy_id}/edit/{order_id}', [OrderController::class, 'edit']);
Route::post('/orders/{pharmacy_id}/edit/{order_id}', [OrderController::class, 'update']);

Route::get('/orders/preview/{order_id}', [OrderController::class, 'preview']);

Route::get('/orders/{pharmacy_id}/facilitys_edit/{order_id}', [FacilityOrderController::class, 'edit']);
Route::post('/orders/{pharmacy_id}/facilitys_edit/{order_id}', [FacilityOrderController::class, 'update']);

Route::get('/orders/{pharmacy_id}/show/{order_id}', [OrderController::class, 'show']);
Route::post('/orders/{pharmacy_id}/show/{order_id}', [OrderController::class, 'handleShowAction']);

Route::get('/orders/{pharmacy_id}/add', [OrderController::class, 'create'])->whereNumber('pharmacy_id')->name('orders.pharmacy.create');
Route::post('/orders/{pharmacy_id}/add', [OrderController::class, 'store'])->whereNumber('pharmacy_id')->name('orders.pharmacy.store');

Route::get('/orders/{pharmacy_id}/facilitys_add', [FacilityOrderController::class, 'create'])->whereNumber('pharmacy_id')->name('orders.pharmacy.facility.create');
Route::post('/orders/{pharmacy_id}/facilitys_add', [FacilityOrderController::class, 'store'])->whereNumber('pharmacy_id')->name('orders.pharmacy.facility.store');

Route::get('/orders/{pharmacy_id}/statistic', [OrderController::class, 'statistic']);
Route::post('/orders/{pharmacy_id}/statistic', [OrderController::class, 'statisticForDate']);

Route::get('/profile', [LexaAdmin::class, 'profile']);
Route::post('/profile', [LexaAdmin::class, 'profileHandler']);

Route::get('/settings/users', [LexaAdmin::class, 'settingsUsers']);
Route::post('/settings/users', [LexaAdmin::class, 'settingsUsersHandler']);

Route::get('/settings/admins', [LexaAdmin::class, 'settingsAdmins']);
Route::post('/settings/admins', [LexaAdmin::class, 'settingsAdminsHandler']);

Route::get('/settings/admin_areas', [LexaAdmin::class, 'settingsAdminAreas']);
Route::get('/settings/admin_areas/add', [LexaAdmin::class, 'settingsAdminAreasAdd']);
Route::post('/settings/admin_areas/add', [LexaAdmin::class, 'settingsAdminAreasAddHandler']);
Route::get('/settings/admin_areas/edit/{admin_area_id}', [LexaAdmin::class, 'settingsAdminAreasEdit']);
Route::post('/settings/admin_areas/edit/{admin_area_id}', [LexaAdmin::class, 'settingsAdminAreasEditHandler']);

Route::get('/settings/medics', [LexaAdmin::class, 'settingsMedics']);
Route::post('/settings/medics', [LexaAdmin::class, 'settingsMedicsHandler']);

Route::get('/settings/drivers', [LexaAdmin::class, 'settingsDrivers']);
Route::post('/settings/drivers', [LexaAdmin::class, 'settingsDriversHandler']);

Route::get('/settings/logists', [LexaAdmin::class, 'settingsLogists']);
Route::post('/settings/logists', [LexaAdmin::class, 'settingsLogistsHandler']);

Route::get('/settings/users/edit/{user_id}', [LexaAdmin::class, 'settingsUsersedit']);
Route::post('/settings/users/edit/{user_id}', [LexaAdmin::class, 'settingsUserseditHandler']);

Route::get('/settings/users/add', [LexaAdmin::class, 'settingsUsersAdd']);
Route::post('/settings/users/add', [LexaAdmin::class, 'settingsUsersAddHandler']);

Route::get('/cards', [LexaAdmin::class, 'cardAdd']);
Route::post('/cards', [LexaAdmin::class, 'cardAddHandler']);

Route::get('/payment-method/{pharmacy_id}', [LexaAdmin::class, 'cardPharmacyAdd']);
Route::post('/payment-method/{pharmacy_id}', [LexaAdmin::class, 'cardPharmacyAddHandler']);

Route::get('/payment-method/{pharmacy_id}/refill', [LexaAdmin::class, 'refillPharmacyBalance']);
Route::post('/payment-method/{pharmacy_id}/refill', [LexaAdmin::class, 'refillPharmacyBalanceHandler']);

Route::get('/notifications', [LexaAdmin::class, 'notifications']);

Route::post('/drivers/qr', [LexaAdmin::class, 'driversQr']);

Route::get('/drivers/{user_id}/packages', [LexaAdmin::class, 'driversPackages']);
Route::post('/drivers/{user_id}/packages', [LexaAdmin::class, 'driversPackagesHandler']);

Route::post('/drivers/qr_order', [LexaAdmin::class, 'driversQrOrder']);

Route::post('/patients/{user_id}/resend', [LexaAdmin::class, 'reSendAuthMessage']);

Route::get('/patients/{user_id}/family', [PatientController::class, 'familyOptions']);
Route::get('/patients/{user_id}/additional_recipients', [PatientController::class, 'additionalRecipientOptions']);

Route::get('/pusher/beams-auth', [LexaAdminApiNoAuth::class, 'pusher_auth']);

Route::get('/news', [LexaAdmin::class, 'news']);
Route::get('/news/add', [LexaAdmin::class, 'newsAdd']);
Route::post('/news/add', [LexaAdmin::class, 'newsAddHandler']);

Route::get('/news_patient', [LexaAdmin::class, 'news_patient']);
Route::get('/news_patient/add', [LexaAdmin::class, 'news_patientAdd']);
Route::post('/news_patient/add', [LexaAdmin::class, 'news_patientAddHandler']);

Route::get('/settings/wishes', [LexaAdmin::class, 'settingsWishesCategory']);
Route::post('/settings/wishes', [LexaAdmin::class, 'settingsWishesCategoryHandler']);

Route::get('/settings/wishes/add', [LexaAdmin::class, 'settingsWishesCategoryAdd']);
Route::post('/settings/wishes/add', [LexaAdmin::class, 'settingsWishesCategoryAddHandler']);

Route::get('/settings/area', [LexaAdmin::class, 'settingsStates']);
Route::post('/settings/area', [LexaAdmin::class, 'settingsStatesHandler']);

Route::get('/settings/area/add', [LexaAdmin::class, 'settingsStatesAdd']);
Route::post('/settings/area/add', [LexaAdmin::class, 'settingsStatesAddHandler']);

Route::get('/settings/area/{area_id}/edit', [LexaAdmin::class, 'settingsStatesEdit']);
Route::post('/settings/area/{area_id}/edit', [LexaAdmin::class, 'settingsStatesEditHandler']);

Route::get('/settings/wishes/{wish_id}/list', [LexaAdmin::class, 'settingsWishes']);
Route::post('/settings/wishes/{wish_id}/list', [LexaAdmin::class, 'settingsWishesHandler']);

Route::get('/settings/wishes/{wish_id}/add', [LexaAdmin::class, 'settingsWishesAdd']);
Route::post('/settings/wishes/{wish_id}/add', [LexaAdmin::class, 'settingsWishesAddHandler']);

Route::get('/settings/plans', [LexaAdmin::class, 'settingsPlans']);
Route::post('/settings/plans', [LexaAdmin::class, 'settingsPlansHandler']);

Route::get('/settings/plans/add', [LexaAdmin::class, 'settingsPlansAdd']);
Route::post('/settings/plans/add', [LexaAdmin::class, 'settingsPlansAddHandler']);

Route::get('/settings/plans/{plan_id}/edit', [LexaAdmin::class, 'settingsPlansEdit']);
Route::post('/settings/plans/{plan_id}/edit', [LexaAdmin::class, 'settingsPlansEditHandler']);

Route::get('/delivery-calendar', [LexaAdmin::class, 'deliveryCalendar']);

Route::get('/ads', [LexaAdmin::class, 'ads']);

Route::get('/payroll', [LexaAdmin::class, 'payroll']);

Route::get('test', [LexaAdmin::class, 'test']);



Route::get('/orders/get_records/{order_id}', [OrderController::class, 'callRecordings']);

Route::get('/support-chat', [LexaAdmin::class, 'supportChat']);

Route::get('/reports', [LexaAdmin::class, 'reports']);
Route::get('/reports/billing', [LexaAdmin::class, 'reportsBilling']);
Route::get('/reports/apps', [LexaAdmin::class, 'reportsApps']);
Route::get('/reports/drivers', [LexaAdmin::class, 'reportsDrivers']);
Route::get('/reports/pharmacies', [LexaAdmin::class, 'reportsPharmacies']);
Route::get('/reports/invoices', [LexaAdmin::class, 'reportsInvoices']);
Route::get('/reports/map', [LexaAdmin::class, 'reportsMap']);
Route::post('/reports/map', [LexaAdmin::class, 'reportsMapHandler']);
Route::get('/reports/customers', [LexaAdmin::class, 'reportsCustomers']);
Route::get('/dispatching', [LexaAdmin::class, 'dispatching']);
Route::post('/dispatching', [LexaAdmin::class, 'dispatchingHandler']);
Route::get('/dispatching/show/{driver_id}', [LexaAdmin::class, 'dispatchingShow']);
Route::post('/dispatching/show/{driver_id}', [LexaAdmin::class, 'dispatchingShowHandler']);
Route::get('/faq', [LexaAdmin::class, 'faq']);
Route::post('/faq', [LexaAdmin::class, 'faqHandler']);
Route::get('/faq/add', [LexaAdmin::class, 'faqAdd']);
Route::post('/faq/add', [LexaAdmin::class, 'faqAddHandler']);

Route::get('/drivers', [LexaAdmin::class, 'drivers']);

Route::post('/ready_call', [LexaAdmin::class, 'ready_call']);

Route::get('/quickbook', [LexaAdmin::class, 'quickbook']);
Route::get('/quickbook_callback', [LexaAdmin::class, 'quickbookCallback']);

Route::get('/feedback', [LexaAdmin::class, 'feedback']);

Route::get('/happy-holidays', [LexaAdmin::class, 'happyHolidays']);

// Route Templates
Route::resource('route-templates', RouteTemplatesController::class)->only(['index', 'store', 'show', 'destroy']);
Route::post('route-templates/{id}/items', [RouteTemplatesController::class, 'updateItems']);
Route::post('route-templates/{id}/assign', [RouteTemplatesController::class, 'assignToDriver']);
