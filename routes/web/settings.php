<?php

use App\Http\Controllers\AdminAreaController;
use App\Http\Controllers\DeliveryAreaController;
use App\Http\Controllers\PlanController;
use App\Http\Controllers\UserSettingsController;
use App\Http\Controllers\WishController;
use Illuminate\Support\Facades\Route;

// Users, admin areas, delivery areas, wishes and plans. Loaded by routes/web.php for signed-in, active accounts.

Route::get('/settings/users', [UserSettingsController::class, 'settingsUsers'])->middleware('allowed:admin');
Route::post('/settings/users', [UserSettingsController::class, 'settingsUsersHandler'])->middleware('allowed:admin');

Route::get('/settings/admins', [UserSettingsController::class, 'settingsAdmins'])->middleware('role:superadmin');
Route::post('/settings/admins', [UserSettingsController::class, 'settingsAdminsHandler'])->middleware('role:superadmin');

Route::get('/settings/admin_areas', [AdminAreaController::class, 'settingsAdminAreas'])->middleware('role:superadmin');
Route::get('/settings/admin_areas/add', [AdminAreaController::class, 'settingsAdminAreasAdd'])->middleware('role:superadmin');
Route::post('/settings/admin_areas/add', [AdminAreaController::class, 'settingsAdminAreasAddHandler'])->middleware('role:superadmin');
Route::get('/settings/admin_areas/edit/{admin_area_id}', [AdminAreaController::class, 'settingsAdminAreasEdit'])->middleware('role:superadmin');
Route::post('/settings/admin_areas/edit/{admin_area_id}', [AdminAreaController::class, 'settingsAdminAreasEditHandler'])->middleware('role:superadmin');

Route::get('/settings/medics', [UserSettingsController::class, 'settingsMedics'])->middleware('allowed:admin');
Route::post('/settings/medics', [UserSettingsController::class, 'settingsMedicsHandler'])->middleware('allowed:admin');

Route::get('/settings/drivers', [UserSettingsController::class, 'settingsDrivers'])->middleware('allowed:admin');
Route::post('/settings/drivers', [UserSettingsController::class, 'settingsDriversHandler'])->middleware('allowed:admin');

Route::get('/settings/logists', [UserSettingsController::class, 'settingsLogists'])->middleware('allowed:admin');
Route::post('/settings/logists', [UserSettingsController::class, 'settingsLogistsHandler'])->middleware('allowed:admin');

Route::get('/settings/users/edit/{user_id}', [UserSettingsController::class, 'settingsUsersedit'])->middleware('allowed:admin');
Route::post('/settings/users/edit/{user_id}', [UserSettingsController::class, 'settingsUserseditHandler'])->middleware('allowed:admin');
Route::get('/settings/users/add', [UserSettingsController::class, 'settingsUsersAdd'])->middleware('allowed:admin');
Route::post('/settings/users/add', [UserSettingsController::class, 'settingsUsersAddHandler'])->middleware('allowed:admin');

Route::get('/settings/wishes', [WishController::class, 'settingsWishesCategory'])->middleware('allowed:admin');
Route::post('/settings/wishes', [WishController::class, 'settingsWishesCategoryHandler'])->middleware('allowed:admin');
Route::get('/settings/wishes/add', [WishController::class, 'settingsWishesCategoryAdd'])->middleware('allowed:admin');
Route::post('/settings/wishes/add', [WishController::class, 'settingsWishesCategoryAddHandler'])->middleware('allowed:admin');

Route::get('/settings/area', [DeliveryAreaController::class, 'settingsStates'])->middleware('allowed:admin');
Route::post('/settings/area', [DeliveryAreaController::class, 'settingsStatesHandler'])->middleware('allowed:admin');
Route::get('/settings/area/add', [DeliveryAreaController::class, 'settingsStatesAdd'])->middleware('allowed:admin');
Route::post('/settings/area/add', [DeliveryAreaController::class, 'settingsStatesAddHandler'])->middleware('allowed:admin');
Route::get('/settings/area/{area_id}/edit', [DeliveryAreaController::class, 'settingsStatesEdit'])->middleware('allowed:admin');
Route::post('/settings/area/{area_id}/edit', [DeliveryAreaController::class, 'settingsStatesEditHandler'])->middleware('allowed:admin');

Route::get('/settings/wishes/{wish_id}/list', [WishController::class, 'settingsWishes'])->middleware('allowed:admin');
Route::post('/settings/wishes/{wish_id}/list', [WishController::class, 'settingsWishesHandler'])->middleware('allowed:admin');
Route::get('/settings/wishes/{wish_id}/add', [WishController::class, 'settingsWishesAdd'])->middleware('allowed:admin');
Route::post('/settings/wishes/{wish_id}/add', [WishController::class, 'settingsWishesAddHandler'])->middleware('allowed:admin');

Route::get('/settings/plans', [PlanController::class, 'settingsPlans'])->middleware('allowed:admin');
Route::post('/settings/plans', [PlanController::class, 'settingsPlansHandler'])->middleware('allowed:admin');
Route::get('/settings/plans/add', [PlanController::class, 'settingsPlansAdd'])->middleware('allowed:admin');
Route::post('/settings/plans/add', [PlanController::class, 'settingsPlansAddHandler'])->middleware('allowed:admin');
Route::get('/settings/plans/{plan_id}/edit', [PlanController::class, 'settingsPlansEdit'])->middleware('allowed:admin');
Route::post('/settings/plans/{plan_id}/edit', [PlanController::class, 'settingsPlansEditHandler'])->middleware('allowed:admin');
