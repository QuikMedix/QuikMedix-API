<?php

use App\Http\Controllers\ImportController;
use App\Http\Controllers\OfficeController;
use App\Http\Controllers\PharmacyController;
use App\Http\Controllers\PharmacyProcessController;
use App\Http\Controllers\PharmacyUserController;
use Illuminate\Support\Facades\Route;

// Pharmacies, their staff, offices, the packing process and order imports. Loaded by routes/web.php for signed-in, active accounts.

Route::get('/pharmacy/{pharmacy_id}/users', [PharmacyUserController::class, 'pharmacyUsers']);
Route::post('/pharmacy/{pharmacy_id}/users', [PharmacyUserController::class, 'pharmacyUsersHandler']);
Route::get('/pharmacy/{pharmacy_id}/users/edit/{user_id}', [PharmacyUserController::class, 'pharmacyUsersEdit']);
Route::post('/pharmacy/{pharmacy_id}/users/edit/{user_id}', [PharmacyUserController::class, 'pharmacyUsersEditHandler']);
Route::get('/pharmacy/{pharmacy_id}/users/add', [PharmacyUserController::class, 'pharmacyUsersAdd']);
Route::post('/pharmacy/{pharmacy_id}/users/add', [PharmacyUserController::class, 'pharmacyUsersAddHandler']);

Route::get('/pharmacys', [PharmacyController::class, 'pharmacysList']);
Route::post('/pharmacys', [PharmacyController::class, 'pharmacysListHandler'])->middleware('allowed:admin');

Route::get('/pharmacys/integrations', [PharmacyController::class, 'pharmacysIntegrations'])->middleware('allowed:admin');
Route::post('/pharmacys/integrations', [PharmacyController::class, 'pharmacysIntegrationsHandler'])->middleware('allowed:admin');

Route::get('/pharmacys/edit/{pharmacy_id}', [PharmacyController::class, 'pharmacysListEdit'])->middleware('allowed:admin');
Route::post('/pharmacys/edit/{pharmacy_id}', [PharmacyController::class, 'pharmacysListEditHandler'])->middleware('allowed:admin');

Route::get('/pharmacys/tariff_map/{pharmacy_id}', [PharmacyController::class, 'pharmacysTariffMap'])->middleware('allowed:admin');

Route::get('/pharmacys/add', [PharmacyController::class, 'pharmacysListAdd']);
Route::post('/pharmacys/add', [PharmacyController::class, 'pharmacysListAddHandler']);

Route::get('/offices', [OfficeController::class, 'officesList'])->middleware('role:superadmin');
Route::post('/offices', [OfficeController::class, 'officesListHandler'])->middleware('role:superadmin');

Route::get('/offices/edit/{office_id}', [OfficeController::class, 'officesListEdit'])->middleware('role:superadmin');
Route::post('/offices/edit/{office_id}', [OfficeController::class, 'officesListEditHandler'])->middleware('role:superadmin');

Route::get('/offices/add', [OfficeController::class, 'officesListAdd'])->middleware('role:superadmin');
Route::post('/offices/add', [OfficeController::class, 'officesListAddHandler'])->middleware('role:superadmin');

Route::get('/process/{pharmacy_id}', [PharmacyProcessController::class, 'process']);
Route::get('/process/{pharmacy_id}/show/{driver_id}', [PharmacyProcessController::class, 'processShow'])->middleware('role:superadmin,admin,dispadmin,medic');

Route::get('/import/{pharmacy_id}/order', [ImportController::class, 'import_order']);
Route::post('/import/{pharmacy_id}/order', [ImportController::class, 'import_orderHandler']);
