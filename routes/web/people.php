<?php

use App\Http\Controllers\DriverController;
use App\Http\Controllers\FacilityController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\PharmacyDriverController;
use App\Http\Controllers\SessionController;
use Illuminate\Support\Facades\Route;

// Drivers, patients and facilities. Loaded by routes/web.php for signed-in, active accounts.

Route::get('/drivers/{pharmacy_id}/users', [PharmacyDriverController::class, 'index']);
Route::post('/drivers/{pharmacy_id}/users', [PharmacyDriverController::class, 'updateStatus']);
Route::get('/drivers/{pharmacy_id}/users/edit/{user_id}', [PharmacyDriverController::class, 'edit']);
Route::post('/drivers/{pharmacy_id}/users/edit/{user_id}', [PharmacyDriverController::class, 'update']);
Route::get('/drivers/{pharmacy_id}/users/add', [PharmacyDriverController::class, 'create']);
Route::post('/drivers/{pharmacy_id}/users/add', [PharmacyDriverController::class, 'store']);
Route::get('/drivers/{driver_id}/profile', [DriverController::class, 'driversProfile'])->middleware('allowed:admin');
Route::post('/drivers/{driver_id}/profile', [DriverController::class, 'driversProfileHandler'])->middleware('allowed:admin');
Route::get('/drivers/{driver_id}/payouts', [DriverController::class, 'driversPayouts'])->middleware('role:superadmin,admin,dispadmin,driver');

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

Route::get('/patients/{pharmacy_id}/import', [ImportController::class, 'patientsImport']);
Route::post('/patients/{pharmacy_id}/import', [ImportController::class, 'patientsImportHandler']);
Route::get('/patients/{pharmacy_id}/removed', [PatientController::class, 'removed']);
Route::get('/patients/{pharmacy_id}/edit/{user_id}', [PatientController::class, 'edit']);
Route::post('/patients/{pharmacy_id}/edit/{user_id}', [PatientController::class, 'update']);

Route::post('/drivers/qr', [DriverController::class, 'driversQr']);
Route::get('/drivers/{user_id}/packages', [DriverController::class, 'driversPackages'])->middleware('role:medic,superadmin,admin,dispadmin');
Route::post('/drivers/{user_id}/packages', [DriverController::class, 'driversPackagesHandler'])->middleware('role:medic,superadmin,admin,dispadmin');
Route::post('/drivers/qr_order', [DriverController::class, 'driversQrOrder']);

Route::post('/patients/{user_id}/resend', [SessionController::class, 'reSendAuthMessage']);
Route::get('/patients/{user_id}/family', [PatientController::class, 'familyOptions']);
Route::get('/patients/{user_id}/additional_recipients', [PatientController::class, 'additionalRecipientOptions']);
