<?php

use App\Http\Controllers\SessionController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\QuickbookController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\NewsController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\DashboardController;
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
| routes are loaded by bootstrap/app.php within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('zadarma', [LexaAdminApiNoAuth::class, 'zadarma_get']);

Route::post('zadarma', [LexaAdminApiNoAuth::class, 'zadarma_post']);

Auth::routes();
Route::get('/logout', [SessionController::class, 'logout']);
Route::get('2fa', [TwoFAController::class, 'index'])->name('2fa.index');
Route::post('2fa', [TwoFAController::class, 'store'])->name('2fa.post');
Route::get('2fa/reset', [TwoFAController::class, 'resend'])->name('2fa.resend');

// Render perticular view file by foldername and filename and all passed in only one controller at a time

// when render first time project redirect
Route::get('/home', [DashboardController::class, 'home']);


// when render first time project redirect
Route::get('/', [DashboardController::class, 'root'])->name('home')->middleware(['auth', '2fa', 'active']);
Route::post('/back-to-superadmin-user', [SessionController::class, 'back_to_superadmin_user'])->name('back_to_superadmin_user');

Route::get('/pharmacy/{pharmacy_id}/users', [PharmacyUserController::class, 'pharmacyUsers'])->middleware(['auth', '2fa', 'active']);
Route::post('/pharmacy/{pharmacy_id}/users', [PharmacyUserController::class, 'pharmacyUsersHandler'])->middleware(['auth', '2fa', 'active']);

Route::get('/pharmacy/{pharmacy_id}/users/edit/{user_id}', [PharmacyUserController::class, 'pharmacyUsersEdit'])->middleware(['auth', '2fa', 'active']);
Route::post('/pharmacy/{pharmacy_id}/users/edit/{user_id}', [PharmacyUserController::class, 'pharmacyUsersEditHandler'])->middleware(['auth', '2fa', 'active']);

Route::get('/pharmacy/{pharmacy_id}/users/add', [PharmacyUserController::class, 'pharmacyUsersAdd'])->middleware(['auth', '2fa', 'active']);
Route::post('/pharmacy/{pharmacy_id}/users/add', [PharmacyUserController::class, 'pharmacyUsersAddHandler'])->middleware(['auth', '2fa', 'active']);

Route::get('/pharmacys', [PharmacyController::class, 'pharmacysList'])->middleware(['auth', '2fa', 'active']);
Route::post('/pharmacys', [PharmacyController::class, 'pharmacysListHandler'])->middleware(['auth', '2fa', 'active', 'allowed:admin']);

Route::get('/pharmacys/integrations', [PharmacyController::class, 'pharmacysIntegrations'])->middleware(['auth', '2fa', 'active', 'allowed:admin']);
Route::post('/pharmacys/integrations', [PharmacyController::class, 'pharmacysIntegrationsHandler'])->middleware(['auth', '2fa', 'active', 'allowed:admin']);

Route::get('/pharmacys/edit/{pharmacy_id}', [PharmacyController::class, 'pharmacysListEdit'])->middleware(['auth', '2fa', 'active', 'allowed:admin']);
Route::post('/pharmacys/edit/{pharmacy_id}', [PharmacyController::class, 'pharmacysListEditHandler'])->middleware(['auth', '2fa', 'active', 'allowed:admin']);

Route::get('/pharmacys/tariff_map/{pharmacy_id}', [PharmacyController::class, 'pharmacysTariffMap'])->middleware(['auth', '2fa', 'active', 'allowed:admin']);

Route::get('/pharmacys/add', [PharmacyController::class, 'pharmacysListAdd']);
Route::post('/pharmacys/add', [PharmacyController::class, 'pharmacysListAddHandler'])->middleware(['auth', '2fa', 'active']);

Route::get('/offices', [OfficeController::class, 'officesList'])->middleware(['auth', '2fa', 'active', 'role:superadmin']);
Route::post('/offices', [OfficeController::class, 'officesListHandler'])->middleware(['auth', '2fa', 'active', 'role:superadmin']);

Route::get('/offices/edit/{office_id}', [OfficeController::class, 'officesListEdit'])->middleware(['auth', '2fa', 'active', 'role:superadmin']);
Route::post('/offices/edit/{office_id}', [OfficeController::class, 'officesListEditHandler'])->middleware(['auth', '2fa', 'active', 'role:superadmin']);

Route::get('/offices/add', [OfficeController::class, 'officesListAdd'])->middleware(['auth', '2fa', 'active', 'role:superadmin']);
Route::post('/offices/add', [OfficeController::class, 'officesListAddHandler'])->middleware(['auth', '2fa', 'active', 'role:superadmin']);

Route::get('/drivers/{pharmacy_id}/users', [PharmacyDriverController::class, 'index']);
Route::post('/drivers/{pharmacy_id}/users', [PharmacyDriverController::class, 'updateStatus']);

Route::get('/drivers/{pharmacy_id}/users/edit/{user_id}', [PharmacyDriverController::class, 'edit']);
Route::post('/drivers/{pharmacy_id}/users/edit/{user_id}', [PharmacyDriverController::class, 'update']);

Route::get('/drivers/{pharmacy_id}/users/add', [PharmacyDriverController::class, 'create']);
Route::post('/drivers/{pharmacy_id}/users/add', [PharmacyDriverController::class, 'store']);

Route::get('/drivers/{driver_id}/profile', [DriverController::class, 'driversProfile'])->middleware(['auth', '2fa', 'allowed:admin']);
Route::post('/drivers/{driver_id}/profile', [DriverController::class, 'driversProfileHandler'])->middleware(['auth', '2fa', 'allowed:admin']);

Route::get('/drivers/{driver_id}/payouts', [DriverController::class, 'driversPayouts'])->middleware(['auth', '2fa', 'active', 'role:superadmin,admin,dispadmin,driver']);

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

Route::get('/patients/{pharmacy_id}/import', [ImportController::class, 'patientsImport'])->middleware(['auth', '2fa', 'active']);
Route::post('/patients/{pharmacy_id}/import', [ImportController::class, 'patientsImportHandler'])->middleware(['auth', '2fa', 'active']);

Route::get('/patients/{pharmacy_id}/removed', [PatientController::class, 'removed']);

Route::get('/patients/{pharmacy_id}/edit/{user_id}', [PatientController::class, 'edit']);
Route::post('/patients/{pharmacy_id}/edit/{user_id}', [PatientController::class, 'update']);

Route::get('/routes-list', [RoutePlanningController::class, 'orders'])->middleware(['auth', '2fa', 'active', 'role:superadmin,admin,dispadmin,logist,medic']);

Route::get('/routes-drivers', [RoutePlanningController::class, 'drivers'])->middleware(['auth', '2fa', 'active', 'role:superadmin,admin,dispadmin,logist,medic']);

Route::get('/routes-pharmacys', [RoutePlanningController::class, 'pharmacies'])->middleware(['auth', '2fa', 'active', 'role:superadmin,admin,dispadmin,logist']);

Route::post('/orders/{pharmacy_id}/ready', [OrderController::class, 'markReady']);

Route::get('/pay/copay/{order_id}', [PaymentController::class, 'payCopay'])->middleware(['auth', '2fa', 'active']);
Route::post('/pay/copay/{order_id}', [PaymentController::class, 'payCopayHandler'])->middleware(['auth', '2fa', 'active']);

Route::get('/routes-list/show/{order_id}', [RoutePlanningController::class, 'showOrder']);
Route::post('/routes-list/show/{order_id}', [RoutePlanningController::class, 'assignOrder']);

Route::get('/routes-list/driver/{driver_id}', [RoutePlanningController::class, 'showDriver']);
Route::post('/routes-list/driver/{driver_id}', [RoutePlanningController::class, 'updateDriver']);

// Global creation links need a pharmacy before opening the scoped form.
Route::get('/orders/add', [OrderCreationController::class, 'create'])->name('orders.create');
Route::get('/orders/facilitys_add', [OrderCreationController::class, 'createFacility'])->name('orders.facility.create');

Route::get('/orders/{pharmacy_id}', [OrderController::class, 'index'])->whereNumber('pharmacy_id');
Route::post('/orders/{pharmacy_id}', [OrderController::class, 'updateStatus'])->whereNumber('pharmacy_id');

Route::get('/search/json', [DashboardController::class, 'searchJson'])->middleware(['auth', '2fa', 'active']);

Route::get('/billing-old/{pharmacy_id}', [BillingController::class, 'billing2'])->middleware(['auth', '2fa', 'active']);

Route::get('/billing/{pharmacy_id}', [BillingController::class, 'billing'])->middleware(['auth', '2fa', 'active']);
Route::get('/billing/{pharmacy_id}/orders/{invoice_id}', [BillingController::class, 'billingOrders'])->middleware(['auth', '2fa', 'active']);
Route::get('/billing/{pharmacy_id}/print/{invoice_id}', [BillingController::class, 'billingPrint'])->middleware(['auth', '2fa', 'active']);
Route::get('/billing/{pharmacy_id}/print_report/{invoice_id}', [BillingController::class, 'billingPrintReport'])->middleware(['auth', '2fa', 'active']);
Route::post('/billing/{pharmacy_id}', [BillingController::class, 'billingHandler'])->middleware(['auth', '2fa', 'active']);

Route::get('/billing/{pharmacy_id}/invoice/add', [BillingController::class, 'billingInvoiceAdd'])->middleware(['auth', '2fa', 'active']);
Route::post('/billing/{pharmacy_id}/invoice/add', [BillingController::class, 'billingInvoiceAddHandler'])->middleware(['auth', '2fa', 'active']);

Route::get('/billing/{pharmacy_id}/invoice/{invoice_id}/edit', [BillingController::class, 'billingInvoiceEdit'])->middleware(['auth', '2fa', 'active']);
Route::post('/billing/{pharmacy_id}/invoice/{invoice_id}/edit', [BillingController::class, 'billingInvoiceEditHandler'])->middleware(['auth', '2fa', 'active']);

Route::get('/process/{pharmacy_id}', [PharmacyProcessController::class, 'process'])->middleware(['auth', '2fa', 'active']);
Route::get('/process/{pharmacy_id}/show/{driver_id}', [PharmacyProcessController::class, 'processShow'])->middleware(['auth', '2fa', 'active', 'role:superadmin,admin,dispadmin,medic']);

Route::get('/import/{pharmacy_id}/order', [ImportController::class, 'import_order'])->middleware(['auth', '2fa', 'active']);
Route::post('/import/{pharmacy_id}/order', [ImportController::class, 'import_orderHandler'])->middleware(['auth', '2fa', 'active']);

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

Route::get('/profile', [ProfileController::class, 'profile'])->middleware(['auth', '2fa', 'active']);
Route::post('/profile', [ProfileController::class, 'profileHandler'])->middleware(['auth', '2fa', 'active']);

Route::get('/settings/users', [UserSettingsController::class, 'settingsUsers'])->middleware(['auth', '2fa', 'active', 'allowed:admin']);
Route::post('/settings/users', [UserSettingsController::class, 'settingsUsersHandler'])->middleware(['auth', '2fa', 'active', 'allowed:admin']);

Route::get('/settings/admins', [UserSettingsController::class, 'settingsAdmins'])->middleware(['auth', '2fa', 'active', 'role:superadmin']);
Route::post('/settings/admins', [UserSettingsController::class, 'settingsAdminsHandler'])->middleware(['auth', '2fa', 'active', 'role:superadmin']);

Route::get('/settings/admin_areas', [AdminAreaController::class, 'settingsAdminAreas'])->middleware(['auth', '2fa', 'active', 'role:superadmin']);
Route::get('/settings/admin_areas/add', [AdminAreaController::class, 'settingsAdminAreasAdd'])->middleware(['auth', '2fa', 'active', 'role:superadmin']);
Route::post('/settings/admin_areas/add', [AdminAreaController::class, 'settingsAdminAreasAddHandler'])->middleware(['auth', '2fa', 'active', 'role:superadmin']);
Route::get('/settings/admin_areas/edit/{admin_area_id}', [AdminAreaController::class, 'settingsAdminAreasEdit'])->middleware(['auth', '2fa', 'active', 'role:superadmin']);
Route::post('/settings/admin_areas/edit/{admin_area_id}', [AdminAreaController::class, 'settingsAdminAreasEditHandler'])->middleware(['auth', '2fa', 'active', 'role:superadmin']);

Route::get('/settings/medics', [UserSettingsController::class, 'settingsMedics'])->middleware(['auth', '2fa', 'active', 'allowed:admin']);
Route::post('/settings/medics', [UserSettingsController::class, 'settingsMedicsHandler'])->middleware(['auth', '2fa', 'active', 'allowed:admin']);

Route::get('/settings/drivers', [UserSettingsController::class, 'settingsDrivers'])->middleware(['auth', '2fa', 'active', 'allowed:admin']);
Route::post('/settings/drivers', [UserSettingsController::class, 'settingsDriversHandler'])->middleware(['auth', '2fa', 'active', 'allowed:admin']);

Route::get('/settings/logists', [UserSettingsController::class, 'settingsLogists'])->middleware(['auth', '2fa', 'active', 'allowed:admin']);
Route::post('/settings/logists', [UserSettingsController::class, 'settingsLogistsHandler'])->middleware(['auth', '2fa', 'active', 'allowed:admin']);

Route::get('/settings/users/edit/{user_id}', [UserSettingsController::class, 'settingsUsersedit'])->middleware(['auth', '2fa', 'active', 'allowed:admin']);
Route::post('/settings/users/edit/{user_id}', [UserSettingsController::class, 'settingsUserseditHandler'])->middleware(['auth', '2fa', 'active', 'allowed:admin']);

Route::get('/settings/users/add', [UserSettingsController::class, 'settingsUsersAdd'])->middleware(['auth', '2fa', 'active', 'allowed:admin']);
Route::post('/settings/users/add', [UserSettingsController::class, 'settingsUsersAddHandler'])->middleware(['auth', '2fa', 'active', 'allowed:admin']);

Route::get('/cards', [PaymentController::class, 'cardAdd'])->middleware(['auth', '2fa', 'active', 'role:superadmin,admin,dispadmin,logist']);
Route::post('/cards', [PaymentController::class, 'cardAddHandler'])->middleware(['auth', '2fa', 'active', 'role:superadmin,admin,dispadmin,logist']);

Route::get('/payment-method/{pharmacy_id}', [PaymentController::class, 'cardPharmacyAdd'])->middleware(['auth', '2fa', 'active']);
Route::post('/payment-method/{pharmacy_id}', [PaymentController::class, 'cardPharmacyAddHandler'])->middleware(['auth', '2fa', 'active']);

Route::get('/payment-method/{pharmacy_id}/refill', [PaymentController::class, 'refillPharmacyBalance'])->middleware(['auth', '2fa', 'active']);
Route::post('/payment-method/{pharmacy_id}/refill', [PaymentController::class, 'refillPharmacyBalanceHandler'])->middleware(['auth', '2fa', 'active']);

Route::get('/notifications', [DashboardController::class, 'notifications']);

Route::post('/drivers/qr', [DriverController::class, 'driversQr']);

Route::get('/drivers/{user_id}/packages', [DriverController::class, 'driversPackages'])->middleware(['auth', '2fa', 'active', 'role:medic,superadmin,admin,dispadmin']);
Route::post('/drivers/{user_id}/packages', [DriverController::class, 'driversPackagesHandler'])->middleware(['auth', '2fa', 'active', 'role:medic,superadmin,admin,dispadmin']);

Route::post('/drivers/qr_order', [DriverController::class, 'driversQrOrder']);

Route::post('/patients/{user_id}/resend', [SessionController::class, 'reSendAuthMessage'])->middleware(['auth', '2fa', 'active']);

Route::get('/patients/{user_id}/family', [PatientController::class, 'familyOptions']);
Route::get('/patients/{user_id}/additional_recipients', [PatientController::class, 'additionalRecipientOptions']);

Route::get('/pusher/beams-auth', [LexaAdminApiNoAuth::class, 'pusher_auth']);

Route::get('/news', [NewsController::class, 'news']);
Route::get('/news/add', [NewsController::class, 'newsAdd'])->middleware(['auth', '2fa', 'active', 'allowed:admin']);
Route::post('/news/add', [NewsController::class, 'newsAddHandler'])->middleware(['auth', '2fa', 'active', 'allowed:admin']);

Route::get('/news_patient', [NewsController::class, 'news_patient']);
Route::get('/news_patient/add', [NewsController::class, 'news_patientAdd'])->middleware(['auth', '2fa', 'active']);
Route::post('/news_patient/add', [NewsController::class, 'news_patientAddHandler'])->middleware(['auth', '2fa', 'active']);

Route::get('/settings/wishes', [WishController::class, 'settingsWishesCategory'])->middleware(['auth', '2fa', 'allowed:admin']);
Route::post('/settings/wishes', [WishController::class, 'settingsWishesCategoryHandler'])->middleware(['auth', '2fa', 'allowed:admin']);

Route::get('/settings/wishes/add', [WishController::class, 'settingsWishesCategoryAdd'])->middleware(['auth', '2fa', 'allowed:admin']);
Route::post('/settings/wishes/add', [WishController::class, 'settingsWishesCategoryAddHandler'])->middleware(['auth', '2fa', 'allowed:admin']);

Route::get('/settings/area', [DeliveryAreaController::class, 'settingsStates'])->middleware(['auth', '2fa', 'allowed:admin']);
Route::post('/settings/area', [DeliveryAreaController::class, 'settingsStatesHandler'])->middleware(['auth', '2fa', 'allowed:admin']);

Route::get('/settings/area/add', [DeliveryAreaController::class, 'settingsStatesAdd'])->middleware(['auth', '2fa', 'allowed:admin']);
Route::post('/settings/area/add', [DeliveryAreaController::class, 'settingsStatesAddHandler'])->middleware(['auth', '2fa', 'allowed:admin']);

Route::get('/settings/area/{area_id}/edit', [DeliveryAreaController::class, 'settingsStatesEdit'])->middleware(['auth', '2fa', 'allowed:admin']);
Route::post('/settings/area/{area_id}/edit', [DeliveryAreaController::class, 'settingsStatesEditHandler'])->middleware(['auth', '2fa', 'allowed:admin']);

Route::get('/settings/wishes/{wish_id}/list', [WishController::class, 'settingsWishes'])->middleware(['auth', '2fa', 'allowed:admin']);
Route::post('/settings/wishes/{wish_id}/list', [WishController::class, 'settingsWishesHandler'])->middleware(['auth', '2fa', 'allowed:admin']);

Route::get('/settings/wishes/{wish_id}/add', [WishController::class, 'settingsWishesAdd'])->middleware(['auth', '2fa', 'allowed:admin']);
Route::post('/settings/wishes/{wish_id}/add', [WishController::class, 'settingsWishesAddHandler'])->middleware(['auth', '2fa', 'allowed:admin']);

Route::get('/settings/plans', [PlanController::class, 'settingsPlans'])->middleware(['auth', '2fa', 'allowed:admin']);
Route::post('/settings/plans', [PlanController::class, 'settingsPlansHandler'])->middleware(['auth', '2fa', 'allowed:admin']);

Route::get('/settings/plans/add', [PlanController::class, 'settingsPlansAdd'])->middleware(['auth', '2fa', 'allowed:admin']);
Route::post('/settings/plans/add', [PlanController::class, 'settingsPlansAddHandler'])->middleware(['auth', '2fa', 'allowed:admin']);

Route::get('/settings/plans/{plan_id}/edit', [PlanController::class, 'settingsPlansEdit'])->middleware(['auth', '2fa', 'allowed:admin']);
Route::post('/settings/plans/{plan_id}/edit', [PlanController::class, 'settingsPlansEditHandler'])->middleware(['auth', '2fa', 'allowed:admin']);

Route::get('/delivery-calendar', [DashboardController::class, 'deliveryCalendar']);

Route::get('/ads', [PageController::class, 'ads']);

Route::get('/payroll', [PageController::class, 'payroll']);




Route::get('/orders/get_records/{order_id}', [OrderController::class, 'callRecordings']);

Route::get('/support-chat', [PageController::class, 'supportChat']);

Route::get('/reports', [ReportController::class, 'reports']);
Route::get('/reports/billing', [ReportController::class, 'reportsBilling']);
Route::get('/reports/apps', [ReportController::class, 'reportsApps']);
Route::get('/reports/drivers', [ReportController::class, 'reportsDrivers']);
Route::get('/reports/pharmacies', [ReportController::class, 'reportsPharmacies']);
Route::get('/reports/invoices', [ReportController::class, 'reportsInvoices']);
Route::get('/reports/map', [ReportController::class, 'reportsMap'])->middleware(['auth', '2fa', 'active', 'allowed:admin']);
Route::post('/reports/map', [ReportController::class, 'reportsMapHandler'])->middleware(['auth', '2fa', 'active', 'allowed:admin']);
Route::get('/reports/customers', [ReportController::class, 'reportsCustomers']);
Route::get('/dispatching', [DispatchingController::class, 'index']);
Route::post('/dispatching', [DispatchingController::class, 'update']);
Route::get('/dispatching/show/{driver_id}', [DispatchingController::class, 'show']);
Route::post('/dispatching/show/{driver_id}', [DispatchingController::class, 'recalculateEta']);
Route::get('/faq', [FaqController::class, 'index']);
Route::post('/faq', [FaqController::class, 'destroy']);
Route::get('/faq/add', [FaqController::class, 'create']);
Route::post('/faq/add', [FaqController::class, 'store']);


Route::post('/ready_call', [DashboardController::class, 'ready_call'])->middleware(['auth', '2fa', 'role:superadmin,admin,dispadmin,logist']);

Route::get('/quickbook', [QuickbookController::class, 'quickbook'])->middleware(['auth', '2fa', 'active', 'allowed:admin']);
Route::get('/quickbook_callback', [QuickbookController::class, 'quickbookCallback'])->middleware(['auth', '2fa', 'active', 'allowed:admin']);

Route::get('/feedback', [PageController::class, 'feedback']);

Route::get('/happy-holidays', [PageController::class, 'happyHolidays']);

// Route Templates
Route::resource('route-templates', RouteTemplatesController::class)->only(['index', 'store', 'show', 'destroy']);
Route::post('route-templates/{id}/items', [RouteTemplatesController::class, 'updateItems']);
Route::post('route-templates/{id}/assign', [RouteTemplatesController::class, 'assignToDriver']);
