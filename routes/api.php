<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\HealthCheckController;
use App\Http\Controllers\Api\PackageBookingController;
use App\Http\Controllers\Api\EmailCheckController;
use App\Http\Controllers\Api\NewsController;
use App\Http\Controllers\Api\DoctorController;
use App\Http\Controllers\Api\JwtAuthController;
use App\Http\Controllers\Diagnostic\DiagnosticServiceController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

use App\Http\Controllers\Api\BookingController;

/*
|--------------------------------------------------------------------------
| JWT Authentication Routes
|--------------------------------------------------------------------------
|
| Modern JWT-based API authentication endpoints.
| Provides login, logout, token refresh, and profile access.
|
*/
Route::prefix('auth')->group(function () {
    Route::post('/login', [JwtAuthController::class, 'login'])->name('api.auth.login');
    Route::post('/refresh', [JwtAuthController::class, 'refresh'])->name('api.auth.refresh');
    
    Route::middleware('jwt.auth')->group(function () {
        Route::get('/profile', [JwtAuthController::class, 'profile'])->name('api.auth.profile');
        Route::post('/logout', [JwtAuthController::class, 'logout'])->name('api.auth.logout');
        Route::post('/logout-all', [JwtAuthController::class, 'logoutAll'])->name('api.auth.logout-all');
    });
});

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('/health-checks', [HealthCheckController::class, 'index']);

Route::middleware(['auth:sanctum', 'web'])->group(function () {
    Route::get('/bookings', [PackageBookingController::class, 'index']);
    Route::post('/bookings', [PackageBookingController::class, 'store']);
});

Route::post('/email-check', [EmailCheckController::class, 'check']);

Route::get('/news', [NewsController::class, 'index']);
Route::get('/news/{id}', [NewsController::class, 'show']);

Route::get('/doctors', [DoctorController::class, 'index']);
Route::get('/diagnostic-services', [DiagnosticServiceController::class, 'list']);
