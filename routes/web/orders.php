<?php

use App\Http\Controllers\AllOrdersController;
use App\Http\Controllers\FacilityOrderController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\OrderCreationController;
use App\Http\Controllers\OrderExceptionController;
use App\Http\Controllers\OrderPrintController;
use App\Http\Controllers\OrderStatusController;
use App\Http\Controllers\PaymentController;
use Illuminate\Support\Facades\Route;

// Orders: lists, creation, editing, status changes, printing and copay payment. Loaded by routes/web.php for signed-in, active accounts.

Route::post('/orders/{pharmacy_id}/ready', [OrderController::class, 'markReady']);

Route::get('/pay/copay/{order_id}', [PaymentController::class, 'payCopay']);
Route::post('/pay/copay/{order_id}', [PaymentController::class, 'payCopayHandler']);

Route::get('/orders/add', [OrderCreationController::class, 'create'])->name('orders.create');
Route::get('/orders/facilitys_add', [OrderCreationController::class, 'createFacility'])->name('orders.facility.create');
Route::get('/orders/{pharmacy_id}', [OrderController::class, 'index'])->whereNumber('pharmacy_id');
Route::post('/orders/{pharmacy_id}', [OrderController::class, 'updateStatus'])->whereNumber('pharmacy_id');
Route::get('/orders', [AllOrdersController::class, 'index'])->middleware('role:superadmin,admin,dispadmin,driver,logist');
Route::post('/orders', [AllOrdersController::class, 'updateStatus'])->middleware('allowed:admin');
Route::get('/orders/day/print', [OrderPrintController::class, 'day']);
Route::get('/orders/ticket/print', [OrderPrintController::class, 'ticket'])->name('orders.ticket')->middleware('role:medic,superadmin,admin,dispadmin,driver,logist');
Route::get('/orders/tickets/print', [OrderPrintController::class, 'tickets'])->middleware('role:medic,superadmin,admin,dispadmin,driver,logist');
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
Route::get('/orders/get_records/{order_id}', [OrderController::class, 'callRecordings'])->middleware('role:superadmin,admin,dispadmin,logist,medic');
