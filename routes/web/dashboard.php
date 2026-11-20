<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FaqController;
use App\Http\Controllers\NewsController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PayrollController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

// The dashboard, search, notices, news, the FAQ and the signed-in person's own profile. Loaded by routes/web.php for signed-in, active accounts.

Route::get('/', [DashboardController::class, 'root'])->name('home');

Route::get('/search/json', [DashboardController::class, 'searchJson']);

Route::get('/profile', [ProfileController::class, 'profile']);
Route::post('/profile', [ProfileController::class, 'profileHandler']);

Route::get('/notifications', [DashboardController::class, 'notifications']);

Route::get('/news', [NewsController::class, 'news']);
Route::get('/news/add', [NewsController::class, 'newsAdd'])->middleware('allowed:admin');
Route::post('/news/add', [NewsController::class, 'newsAddHandler'])->middleware('allowed:admin');

Route::get('/news_patient', [NewsController::class, 'news_patient']);
Route::get('/news_patient/add', [NewsController::class, 'news_patientAdd']);
Route::post('/news_patient/add', [NewsController::class, 'news_patientAddHandler']);

Route::get('/delivery-calendar', [DashboardController::class, 'deliveryCalendar']);

Route::get('/ads', [PageController::class, 'ads']);

Route::get('/payroll', [PayrollController::class, 'index']);
Route::post('/payroll', [PayrollController::class, 'update']);

Route::get('/support-chat', [PageController::class, 'supportChat']);

Route::post('/faq', [FaqController::class, 'destroy'])->middleware('allowed:admin');
Route::get('/faq/add', [FaqController::class, 'create'])->middleware('allowed:admin');
Route::post('/faq/add', [FaqController::class, 'store'])->middleware('allowed:admin');

Route::post('/ready_call', [DashboardController::class, 'ready_call'])->middleware('role:superadmin,admin,dispadmin,logist');

Route::get('/feedback', [PageController::class, 'feedback']);

Route::get('/happy-holidays', [PageController::class, 'happyHolidays']);
