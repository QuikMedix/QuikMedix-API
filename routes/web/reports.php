<?php

use App\Http\Controllers\ReportController;
use Illuminate\Support\Facades\Route;

// Reports. Loaded by routes/web.php for signed-in, active accounts.

// The menu shows Reports to admin staff only; the pages themselves are limited the same way.
Route::middleware('allowed:admin')->group(function (): void {
    Route::get('/reports', [ReportController::class, 'reports']);
    Route::get('/reports/billing', [ReportController::class, 'reportsBilling']);
    Route::get('/reports/apps', [ReportController::class, 'reportsApps']);
    Route::get('/reports/drivers', [ReportController::class, 'reportsDrivers']);
    Route::get('/reports/pharmacies', [ReportController::class, 'reportsPharmacies']);
    Route::get('/reports/invoices', [ReportController::class, 'reportsInvoices']);
    Route::get('/reports/map', [ReportController::class, 'reportsMap']);
    Route::post('/reports/map', [ReportController::class, 'reportsMapHandler']);
    Route::get('/reports/customers', [ReportController::class, 'reportsCustomers']);
});
