<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
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
| Mata Kuliah: CIE61207
| Sistem Manajemen Laundry - 5 Anggota
|
| 1. service-types          : Jenis layanan laundry (Anggota 1)
| 2. laundry-orders         : Pesanan laundry (Anggota 2)
| 3. payments               : Pembayaran (Anggota 3)
| 4. pickup-schedules       : Jadwal pickup/delivery (Anggota 4)
| 5. order-status-histories : Riwayat status pesanan (Anggota 5)
| +  users                  : Manajemen user & relasi
|--------------------------------------------------------------------------
*/

// Cek status API
Route::get('/', function () {
    return response()->json([
        'success' => true,
        'message' => 'WashPoint REST API is running successfully',
        'version' => '1.0.0',
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

// Resource Routes CRUD (GET, POST, PUT, PATCH, DELETE)
Route::apiResource('users', UserController::class);
Route::apiResource('service-types', ServiceTypeController::class);
Route::apiResource('laundry-orders', LaundryOrderController::class);
Route::apiResource('payments', PaymentController::class);
Route::apiResource('pickup-schedules', PickupScheduleController::class);
Route::apiResource('order-status-histories', OrderStatusHistoryController::class);
