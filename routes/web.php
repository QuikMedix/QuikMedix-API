<?php

use App\Http\Controllers\LexaAdmin;
use App\Http\Controllers\WishController;
use App\Http\Controllers\PlanController;
use App\Http\Controllers\DeliveryAreaController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\AdminAreaController;
use App\Http\Controllers\UserSettingsController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\DriverController;
use App\Http\Controllers\OfficeController;
use App\Http\Controllers\PharmacyProcessController;
use App\Http\Controllers\PharmacyUserController;
use App\Http\Controllers\PharmacyController;
use App\Http\Controllers\RoutePlanningController;
use App\Http\Controllers\DispatchingController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\FacilityOrderController;
use App\Http\Controllers\OrderPrintController;
use App\Http\Controllers\OrderExceptionController;
use App\Http\Controllers\OrderStatusController;
use App\Http\Controllers\AllOrdersController;
use App\Http\Controllers\LexaAdminApiNoAuth;
use App\Http\Controllers\FacilityController;
use App\Http\Controllers\FaqController;
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

// when render first time project redirect
Route::get('/home', [LexaAdmin::class, 'home']);

Route::get('/keep-live', [LexaAdmin::class, 'live']);

// when render first time project redirect
Route::get('/', [LexaAdmin::class, 'root'])->name('home');
Route::post('/back-to-superadmin-user', [LexaAdmin::class, 'back_to_superadmin_user'])->name('back_to_superadmin_user');

Route::get('/pharmacy/{pharmacy_id}/users', [PharmacyUserController::class, 'pharmacyUsers']);
Route::post('/pharmacy/{pharmacy_id}/users', [PharmacyUserController::class, 'pharmacyUsersHandler']);

Route::get('/pharmacy/{pharmacy_id}/users/edit/{user_id}', [PharmacyUserController::class, 'pharmacyUsersEdit']);
Route::post('/pharmacy/{pharmacy_id}/users/edit/{user_id}', [PharmacyUserController::class, 'pharmacyUsersEditHandler']);

Route::get('/pharmacy/{pharmacy_id}/users/add', [PharmacyUserController::class, 'pharmacyUsersAdd']);
Route::post('/pharmacy/{pharmacy_id}/users/add', [PharmacyUserController::class, 'pharmacyUsersAddHandler']);

Route::get('/pharmacys', [PharmacyController::class, 'pharmacysList']);
Route::post('/pharmacys', [PharmacyController::class, 'pharmacysListHandler']);

Route::get('/pharmacys/integrations', [PharmacyController::class, 'pharmacysIntegrations']);
Route::post('/pharmacys/integrations', [PharmacyController::class, 'pharmacysIntegrationsHandler']);

Route::get('/pharmacys/edit/{pharmacy_id}', [PharmacyController::class, 'pharmacysListEdit']);
Route::post('/pharmacys/edit/{pharmacy_id}', [PharmacyController::class, 'pharmacysListEditHandler']);

Route::get('/pharmacys/tariff_map/{pharmacy_id}', [PharmacyController::class, 'pharmacysTariffMap']);

Route::get('/pharmacys/add', [PharmacyController::class, 'pharmacysListAdd']);
Route::post('/pharmacys/add', [PharmacyController::class, 'pharmacysListAddHandler']);

Route::get('/offices', [OfficeController::class, 'officesList']);
Route::post('/offices', [OfficeController::class, 'officesListHandler']);

Route::get('/offices/edit/{office_id}', [OfficeController::class, 'officesListEdit']);
Route::post('/offices/edit/{office_id}', [OfficeController::class, 'officesListEditHandler']);

Route::get('/offices/add', [OfficeController::class, 'officesListAdd']);
Route::post('/offices/add', [OfficeController::class, 'officesListAddHandler']);

Route::get('/drivers/{pharmacy_id}/users', [PharmacyDriverController::class, 'index']);
Route::post('/drivers/{pharmacy_id}/users', [PharmacyDriverController::class, 'updateStatus']);

Route::get('/drivers/{pharmacy_id}/users/edit/{user_id}', [PharmacyDriverController::class, 'edit']);
Route::post('/drivers/{pharmacy_id}/users/edit/{user_id}', [PharmacyDriverController::class, 'update']);

Route::get('/drivers/{pharmacy_id}/users/add', [PharmacyDriverController::class, 'create']);
Route::post('/drivers/{pharmacy_id}/users/add', [PharmacyDriverController::class, 'store']);

Route::get('/drivers/{driver_id}/profile', [DriverController::class, 'driversProfile']);
Route::post('/drivers/{driver_id}/profile', [DriverController::class, 'driversProfileHandler']);

Route::get('/drivers/{driver_id}/payouts', [DriverController::class, 'driversPayouts']);

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

Route::get('/routes-list', [RoutePlanningController::class, 'orders']);

Route::get('/routes-drivers', [RoutePlanningController::class, 'drivers']);

Route::get('/routes-pharmacys', [RoutePlanningController::class, 'pharmacies']);

Route::post('/orders/{pharmacy_id}/ready', [OrderController::class, 'markReady']);

Route::get('/pay/copay/{order_id}', [PaymentController::class, 'payCopay']);
Route::post('/pay/copay/{order_id}', [PaymentController::class, 'payCopayHandler']);

Route::get('/routes-list/show/{order_id}', [RoutePlanningController::class, 'showOrder']);
Route::post('/routes-list/show/{order_id}', [RoutePlanningController::class, 'assignOrder']);

Route::get('/routes-list/driver/{driver_id}', [RoutePlanningController::class, 'showDriver']);
Route::post('/routes-list/driver/{driver_id}', [RoutePlanningController::class, 'updateDriver']);

// Global creation links need a pharmacy before opening the scoped form.
Route::get('/orders/add', [OrderCreationController::class, 'create'])->name('orders.create');
Route::get('/orders/facilitys_add', [OrderCreationController::class, 'createFacility'])->name('orders.facility.create');

Route::get('/orders/{pharmacy_id}', [OrderController::class, 'index'])->whereNumber('pharmacy_id');
Route::post('/orders/{pharmacy_id}', [OrderController::class, 'updateStatus'])->whereNumber('pharmacy_id');

Route::get('/search/json', [LexaAdmin::class, 'searchJson']);

Route::get('/billing-old/{pharmacy_id}', [BillingController::class, 'billing2']);

Route::get('/billing/{pharmacy_id}', [BillingController::class, 'billing']);
Route::get('/billing/{pharmacy_id}/orders/{invoice_id}', [BillingController::class, 'billingOrders']);
Route::get('/billing/{pharmacy_id}/print/{invoice_id}', [BillingController::class, 'billingPrint']);
Route::get('/billing/{pharmacy_id}/print_report/{invoice_id}', [BillingController::class, 'billingPrintReport']);
Route::post('/billing/{pharmacy_id}', [BillingController::class, 'billingHandler']);

Route::get('/billing/{pharmacy_id}/invoice/add', [BillingController::class, 'billingInvoiceAdd']);
Route::post('/billing/{pharmacy_id}/invoice/add', [BillingController::class, 'billingInvoiceAddHandler']);

Route::get('/billing/{pharmacy_id}/invoice/{invoice_id}/edit', [BillingController::class, 'billingInvoiceEdit']);
Route::post('/billing/{pharmacy_id}/invoice/{invoice_id}/edit', [BillingController::class, 'billingInvoiceEditHandler']);

Route::get('/process/{pharmacy_id}', [PharmacyProcessController::class, 'process']);
Route::get('/process/{pharmacy_id}/show/{driver_id}', [PharmacyProcessController::class, 'processShow']);

Route::get('/import/{pharmacy_id}/order', [LexaAdmin::class, 'import_order']);
Route::post('/import/{pharmacy_id}/order', [LexaAdmin::class, 'import_orderHandler']);

Route::get('/orders', [AllOrdersController::class, 'index']);
Route::post('/orders', [AllOrdersController::class, 'updateStatus']);

Route::get('/orders/day/print', [OrderPrintController::class, 'day']);
Route::get('/orders/ticket/print', [OrderPrintController::class, 'ticket'])->name('orders.ticket');
Route::get('/orders/tickets/print', [OrderPrintController::class, 'tickets']);

Route::get('/orders/{pharmacy_id}/edit/{order_id}', [OrderController::class, 'edit']);
Route::post('/orders/{pharmacy_id}/edit/{order_id}', [OrderController::class, 'update']);

Route::get('/orders/preview/{order_id}', [OrderController::class, 'preview']);

Route::get('/orders/{pharmacy_id}/facilitys_edit/{order_id}', [FacilityOrderController::class, 'edit']);
Route::post('/orders/{pharmacy_id}/facilitys_edit/{order_id}', [FacilityOrderController::class, 'update']);

Route::get('/orders/{pharmacy_id}/show/{order_id}', [OrderController::class, 'show'])->name('orders.show');
Route::post('/orders/{pharmacy_id}/status/{order_id}', [OrderStatusController::class, 'update'])->whereNumber(['pharmacy_id', 'order_id'])->name('orders.status.update');
Route::post('/orders/{pharmacy_id}/exceptions/{order_id}/{exception_id}/approve', [OrderExceptionController::class, 'approve'])->whereNumber(['pharmacy_id', 'order_id', 'exception_id'])->name('orders.exceptions.approve');
Route::post('/orders/{pharmacy_id}/exceptions/{order_id}/{exception_id}/reject', [OrderExceptionController::class, 'reject'])->whereNumber(['pharmacy_id', 'order_id', 'exception_id'])->name('orders.exceptions.reject');
Route::post('/orders/{pharmacy_id}/show/{order_id}', [OrderController::class, 'handleShowAction']);

Route::get('/orders/{pharmacy_id}/add', [OrderController::class, 'create'])->whereNumber('pharmacy_id')->name('orders.pharmacy.create');
Route::post('/orders/{pharmacy_id}/add', [OrderController::class, 'store'])->whereNumber('pharmacy_id')->name('orders.pharmacy.store');

Route::get('/orders/{pharmacy_id}/facilitys_add', [FacilityOrderController::class, 'create'])->whereNumber('pharmacy_id')->name('orders.pharmacy.facility.create');
Route::post('/orders/{pharmacy_id}/facilitys_add', [FacilityOrderController::class, 'store'])->whereNumber('pharmacy_id')->name('orders.pharmacy.facility.store');

Route::get('/orders/{pharmacy_id}/statistic', [OrderController::class, 'statistic']);
Route::post('/orders/{pharmacy_id}/statistic', [OrderController::class, 'statisticForDate']);

Route::get('/profile', [ProfileController::class, 'profile']);
Route::post('/profile', [ProfileController::class, 'profileHandler']);

Route::get('/settings/users', [UserSettingsController::class, 'settingsUsers']);
Route::post('/settings/users', [UserSettingsController::class, 'settingsUsersHandler']);

Route::get('/settings/admins', [UserSettingsController::class, 'settingsAdmins']);
Route::post('/settings/admins', [UserSettingsController::class, 'settingsAdminsHandler']);

Route::get('/settings/admin_areas', [AdminAreaController::class, 'settingsAdminAreas']);
Route::get('/settings/admin_areas/add', [AdminAreaController::class, 'settingsAdminAreasAdd']);
Route::post('/settings/admin_areas/add', [AdminAreaController::class, 'settingsAdminAreasAddHandler']);
Route::get('/settings/admin_areas/edit/{admin_area_id}', [AdminAreaController::class, 'settingsAdminAreasEdit']);
Route::post('/settings/admin_areas/edit/{admin_area_id}', [AdminAreaController::class, 'settingsAdminAreasEditHandler']);

Route::get('/settings/medics', [UserSettingsController::class, 'settingsMedics']);
Route::post('/settings/medics', [UserSettingsController::class, 'settingsMedicsHandler']);

Route::get('/settings/drivers', [UserSettingsController::class, 'settingsDrivers']);
Route::post('/settings/drivers', [UserSettingsController::class, 'settingsDriversHandler']);

Route::get('/settings/logists', [UserSettingsController::class, 'settingsLogists']);
Route::post('/settings/logists', [UserSettingsController::class, 'settingsLogistsHandler']);

Route::get('/settings/users/edit/{user_id}', [UserSettingsController::class, 'settingsUsersedit']);
Route::post('/settings/users/edit/{user_id}', [UserSettingsController::class, 'settingsUserseditHandler']);

Route::get('/settings/users/add', [UserSettingsController::class, 'settingsUsersAdd']);
Route::post('/settings/users/add', [UserSettingsController::class, 'settingsUsersAddHandler']);

Route::get('/cards', [PaymentController::class, 'cardAdd']);
Route::post('/cards', [PaymentController::class, 'cardAddHandler']);

Route::get('/payment-method/{pharmacy_id}', [PaymentController::class, 'cardPharmacyAdd']);
Route::post('/payment-method/{pharmacy_id}', [PaymentController::class, 'cardPharmacyAddHandler']);

Route::get('/payment-method/{pharmacy_id}/refill', [PaymentController::class, 'refillPharmacyBalance']);
Route::post('/payment-method/{pharmacy_id}/refill', [PaymentController::class, 'refillPharmacyBalanceHandler']);

Route::get('/notifications', [LexaAdmin::class, 'notifications']);

Route::post('/drivers/qr', [DriverController::class, 'driversQr']);

Route::get('/drivers/{user_id}/packages', [DriverController::class, 'driversPackages']);
Route::post('/drivers/{user_id}/packages', [DriverController::class, 'driversPackagesHandler']);

Route::post('/drivers/qr_order', [DriverController::class, 'driversQrOrder']);

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

Route::get('/settings/wishes', [WishController::class, 'settingsWishesCategory']);
Route::post('/settings/wishes', [WishController::class, 'settingsWishesCategoryHandler']);

Route::get('/settings/wishes/add', [WishController::class, 'settingsWishesCategoryAdd']);
Route::post('/settings/wishes/add', [WishController::class, 'settingsWishesCategoryAddHandler']);

Route::get('/settings/area', [DeliveryAreaController::class, 'settingsStates']);
Route::post('/settings/area', [DeliveryAreaController::class, 'settingsStatesHandler']);

Route::get('/settings/area/add', [DeliveryAreaController::class, 'settingsStatesAdd']);
Route::post('/settings/area/add', [DeliveryAreaController::class, 'settingsStatesAddHandler']);

Route::get('/settings/area/{area_id}/edit', [DeliveryAreaController::class, 'settingsStatesEdit']);
Route::post('/settings/area/{area_id}/edit', [DeliveryAreaController::class, 'settingsStatesEditHandler']);

Route::get('/settings/wishes/{wish_id}/list', [WishController::class, 'settingsWishes']);
Route::post('/settings/wishes/{wish_id}/list', [WishController::class, 'settingsWishesHandler']);

Route::get('/settings/wishes/{wish_id}/add', [WishController::class, 'settingsWishesAdd']);
Route::post('/settings/wishes/{wish_id}/add', [WishController::class, 'settingsWishesAddHandler']);

Route::get('/settings/plans', [PlanController::class, 'settingsPlans']);
Route::post('/settings/plans', [PlanController::class, 'settingsPlansHandler']);

Route::get('/settings/plans/add', [PlanController::class, 'settingsPlansAdd']);
Route::post('/settings/plans/add', [PlanController::class, 'settingsPlansAddHandler']);

Route::get('/settings/plans/{plan_id}/edit', [PlanController::class, 'settingsPlansEdit']);
Route::post('/settings/plans/{plan_id}/edit', [PlanController::class, 'settingsPlansEditHandler']);

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
Route::get('/dispatching', [DispatchingController::class, 'index']);
Route::post('/dispatching', [DispatchingController::class, 'update']);
Route::get('/dispatching/show/{driver_id}', [DispatchingController::class, 'show']);
Route::post('/dispatching/show/{driver_id}', [DispatchingController::class, 'recalculateEta']);
Route::get('/faq', [FaqController::class, 'index']);
Route::post('/faq', [FaqController::class, 'destroy']);
Route::get('/faq/add', [FaqController::class, 'create']);
Route::post('/faq/add', [FaqController::class, 'store']);

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
