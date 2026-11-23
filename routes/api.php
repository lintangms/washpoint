<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ServiceTypeController;
use App\Http\Controllers\LaundryOrderController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PickupScheduleController;
use App\Http\Controllers\OrderStatusHistoryController;

/*
|--------------------------------------------------------------------------
| WashPoint REST API Routes (Laravel 12)
|--------------------------------------------------------------------------
| Mata Kuliah: CIE61207 - Sistem Manajemen Laundry
|
| Konsep Autentikasi & Otorisasi:
| - Authentication : Laravel Sanctum (auth:sanctum)
| - Authorization  : Middleware 'role.cru_crud'
|   * Role 'user'  : Memiliki hak akses CRU (Create, Read, Update)
|   * Role 'admin' : Memiliki hak akses CRUD (Create, Read, Update, Delete)
|   * Akses DELETE oleh role 'user' akan ditolak (403 Forbidden)
|
| Modul Anggota Kelompok:
| 1. service-types          : Jenis layanan laundry (Anggota 1)
| 2. laundry-orders         : Pesanan laundry (Anggota 2)
| 3. payments               : Pembayaran (Anggota 3)
| 4. pickup-schedules       : Jadwal pickup/delivery (Anggota 4)
| 5. order-status-histories : Riwayat status pesanan (Anggota 5)
| +  users                  : Manajemen user & relasi
|--------------------------------------------------------------------------
*/

// ==========================================
// 1. PUBLIC ROUTES (Tanpa Autentikasi)
// ==========================================
Route::get('/', function () {
    return response()->json([
        'success' => true,
        'message' => 'WashPoint REST API is running successfully',
        'version' => '1.1.0',
        'auth_endpoints' => [
            'register' => 'POST /api/register',
            'login' => 'POST /api/login',
        ],
        'resources' => [
            'users' => '/api/users',
            'service_types' => '/api/service-types',
            'laundry_orders' => '/api/laundry-orders',
            'payments' => '/api/payments',
            'pickup_schedules' => '/api/pickup-schedules',
            'order_status_histories' => '/api/order-status-histories',
        ]
    ]);
});

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// ==========================================
// 2. PROTECTED ROUTES (Middleware: jwt.auth)
// Mendukung JWT Bearer Token (panjang)
// ==========================================
Route::middleware('jwt.auth')->group(function () {

    // Auth Utilities
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // ==========================================
    // 3. RESOURCE ROUTES DENGAN OTORISASI CRU/CRUD
    // Middleware: 'role.cru_crud'
    // - User  : CRU (GET, POST, PUT, PATCH)
    // - Admin : CRUD (GET, POST, PUT, PATCH, DELETE)
    // ==========================================
    Route::middleware('role.cru_crud')->group(function () {
        Route::apiResource('users', UserController::class);
        Route::apiResource('service-types', ServiceTypeController::class);
        Route::apiResource('laundry-orders', LaundryOrderController::class);
        Route::apiResource('payments', PaymentController::class);
        Route::apiResource('pickup-schedules', PickupScheduleController::class);
        Route::apiResource('order-status-histories', OrderStatusHistoryController::class);
    });
});
