<?php

use App\Http\Controllers\PatientCardController;
use App\Http\Controllers\Auth\ConfirmPasswordController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FaqController;
use App\Http\Controllers\LexaAdminApiNoAuth;
use App\Http\Controllers\SessionController;
use App\Http\Controllers\TwoFAController;
use App\Http\Middleware\LiftMemoryLimit;
use Illuminate\Support\Facades\Route;

// Called by outside services, which have no session.
Route::get('zadarma', [LexaAdminApiNoAuth::class, 'zadarma_get']);
Route::post('zadarma', [LexaAdminApiNoAuth::class, 'zadarma_post']);
Route::get('/pusher/beams-auth', [LexaAdminApiNoAuth::class, 'pusher_auth']);

// Signing in, registering and passwords.
// The patient app opens this page through a signed, expiring link; the link is the sign-in.
Route::middleware('signed:relative')->group(function () {
    Route::get('patient/card/{user}', [PatientCardController::class, 'show'])->name('patient.card');
    Route::post('patient/card/{user}', [PatientCardController::class, 'store']);
});
Route::middleware('guest')->group(function () {
    Route::get('login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('login', [LoginController::class, 'login']);
    Route::get('register', [RegisterController::class, 'showRegistrationForm'])->name('register');
    Route::post('register', [RegisterController::class, 'register']);
});
Route::post('logout', [LoginController::class, 'logout'])->name('logout');
Route::get('password/reset', [ForgotPasswordController::class, 'showLinkRequestForm'])->name('password.request');
Route::post('password/email', [ForgotPasswordController::class, 'sendResetLinkEmail'])->name('password.email');
Route::get('password/reset/{token}', [ResetPasswordController::class, 'showResetForm'])->name('password.reset');
Route::post('password/reset', [ResetPasswordController::class, 'reset'])->name('password.update');
Route::middleware('auth')->group(function () {
    Route::get('password/confirm', [ConfirmPasswordController::class, 'showConfirmForm'])->name('password.confirm');
    Route::post('password/confirm', [ConfirmPasswordController::class, 'confirm']);
});

// The second sign-in step.
Route::get('2fa', [TwoFAController::class, 'index'])->name('2fa.index');
Route::post('2fa', [TwoFAController::class, 'store'])->name('2fa.post');
Route::get('2fa/reset', [TwoFAController::class, 'resend'])->name('2fa.resend');

// Signed in, and open to blocked accounts too: leaving, returning to the superadmin, and the FAQ.
Route::middleware(['auth', '2fa'])->group(function () {
    Route::get('/logout', [SessionController::class, 'logout']);
    Route::get('/home', [DashboardController::class, 'home']);
    Route::post('/back-to-superadmin-user', [SessionController::class, 'back_to_superadmin_user'])->name('back_to_superadmin_user');
    Route::get('/faq', [FaqController::class, 'index']);
});

// Everything else needs a signed-in, active account. A route adds role: or allowed: where only some roles may enter.
Route::middleware(['auth', '2fa', 'active', LiftMemoryLimit::class])->group(function () {
    require __DIR__.'/web/dashboard.php';
    require __DIR__.'/web/pharmacies.php';
    require __DIR__.'/web/people.php';
    require __DIR__.'/web/orders.php';
    require __DIR__.'/web/dispatch.php';
    require __DIR__.'/web/billing.php';
    require __DIR__.'/web/settings.php';
    require __DIR__.'/web/reports.php';
});
