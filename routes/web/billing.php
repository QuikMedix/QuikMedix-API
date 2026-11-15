<?php

use App\Http\Controllers\BillingController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\QuickbookController;
use Illuminate\Support\Facades\Route;

// Invoices, payment methods and QuickBooks. Loaded by routes/web.php for signed-in, active accounts.

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

Route::get('/cards', [PaymentController::class, 'cardAdd'])->middleware('role:superadmin,admin,dispadmin,logist');
Route::post('/cards', [PaymentController::class, 'cardAddHandler'])->middleware('role:superadmin,admin,dispadmin,logist');

Route::get('/payment-method/{pharmacy_id}', [PaymentController::class, 'cardPharmacyAdd']);
Route::post('/payment-method/{pharmacy_id}', [PaymentController::class, 'cardPharmacyAddHandler']);
Route::get('/payment-method/{pharmacy_id}/refill', [PaymentController::class, 'refillPharmacyBalance']);
Route::post('/payment-method/{pharmacy_id}/refill', [PaymentController::class, 'refillPharmacyBalanceHandler']);

Route::get('/quickbook', [QuickbookController::class, 'quickbook'])->middleware('allowed:admin');

Route::get('/quickbook_callback', [QuickbookController::class, 'quickbookCallback'])->middleware('allowed:admin');
