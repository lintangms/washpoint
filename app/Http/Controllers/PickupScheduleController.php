<?php

namespace App\Http\Controllers;

use App\Models\PickupSchedule;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Exception;

class PickupScheduleController extends Controller
{
    /**
     * Anggota 4: Display a listing of pickup schedules.
     */
    public function index()
    {
        try {
            $schedules = PickupSchedule::with(['user', 'laundryOrder'])->latest()->get();

            return response()->json([
                'success' => true,
                'message' => 'Daftar jadwal penjemputan berhasil diambil',
                'data' => $schedules
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data jadwal: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Anggota 4: Store a newly created pickup schedule.
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'user_id' => 'nullable|exists:users,id',
                'laundry_order_id' => 'nullable|exists:laundry_orders,id',
                'scheduled_at' => 'nullable|date',
                'type' => 'nullable|string|max:20',
                'address' => 'required|string|max:255',
                'status' => 'nullable|string|max:30',
            ]);

            $authUser = $request->user();
            $userId = ($authUser && $authUser->isAdmin() && isset($validated['user_id']))
                ? $validated['user_id']
                : ($authUser?->id ?? \App\Models\User::first()?->id ?? 1);

            $orderId = $validated['laundry_order_id'] ?? \App\Models\LaundryOrder::latest()->first()?->id;
            if (!$orderId) {
                $serviceTypeId = \App\Models\ServiceType::first()?->id;
                if (!$serviceTypeId) {
                    $svc = \App\Models\ServiceType::create([
                        'user_id' => $userId,
                        'name' => 'Cuci Kilat',
                        'price_per_kg' => 8000,
                        'estimated_hours' => 24,
                        'is_active' => true,
                    ]);
                    $serviceTypeId = $svc->id;
                }

                $newOrder = \App\Models\LaundryOrder::create([
                    'user_id' => $userId,
                    'service_type_id' => $serviceTypeId,
                    'customer_name' => 'Pelanggan Umum',
                    'customer_phone' => '081234567899',
                    'weight_kg' => 3.0,
                    'total_price' => 24000,
                    'status' => 'received',
                ]);
                $orderId = $newOrder->id;
            }

            $schedule = PickupSchedule::create([
                'user_id' => $userId,
                'laundry_order_id' => $orderId,
                'scheduled_at' => $validated['scheduled_at'] ?? now()->addDay(),
                'type' => $validated['type'] ?? 'pickup',
                'address' => $validated['address'],
                'status' => $validated['status'] ?? 'scheduled',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Jadwal penjemputan berhasil dibuat',
                'data' => $schedule->load(['user', 'laundryOrder'])
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat jadwal penjemputan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Anggota 4: Display the specified pickup schedule.
     */
    public function show(string $id)
    {
        try {
            $schedule = PickupSchedule::with(['user', 'laundryOrder'])->find($id);

            if (!$schedule) {
                return response()->json([
                    'success' => false,
                    'message' => 'Jadwal penjemputan tidak ditemukan',
                    'data' => null
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Detail jadwal penjemputan berhasil diambil',
                'data' => $schedule
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil detail jadwal penjemputan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Anggota 4: Update the specified pickup schedule (PUT / PATCH).
     */
    public function update(Request $request, string $id)
    {
        try {
            $schedule = PickupSchedule::find($id);

            if (!$schedule) {
                return response()->json([
                    'success' => false,
                    'message' => 'Jadwal penjemputan tidak ditemukan',
                    'data' => null
                ], 404);
            }

            $authUser = $request->user();
            if ($authUser && !$authUser->isAdmin() && (int) $schedule->user_id !== (int) $authUser->id) {
                return response()->json([
                    'success' => false,
                    'message' => "Akses ditolak (Forbidden). Role 'user' hanya diizinkan mengubah jadwal penjemputan miliknya sendiri."
                ], 403);
            }

            $validated = $request->validate([
                'user_id' => 'sometimes|required|exists:users,id',
                'laundry_order_id' => 'sometimes|required|exists:laundry_orders,id',
                'scheduled_at' => 'sometimes|required|date',
                'type' => 'nullable|string|max:20',
                'address' => 'sometimes|required|string|max:255',
                'status' => 'nullable|string|max:30',
            ]);

            if ($authUser && !$authUser->isAdmin()) {
                unset($validated['user_id']);
            }

            $schedule->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Jadwal penjemputan berhasil diperbarui',
                'data' => $schedule->fresh()->load(['user', 'laundryOrder'])
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui jadwal penjemputan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Anggota 4: Remove the specified pickup schedule.
     */
    public function destroy(string $id)
    {
        try {
            $schedule = PickupSchedule::find($id);

            if (!$schedule) {
                return response()->json([
                    'success' => false,
                    'message' => 'Jadwal penjemputan tidak ditemukan',
                    'data' => null
                ], 404);
            }

            $schedule->delete();

            return response()->json([
                'success' => true,
                'message' => 'Jadwal penjemputan berhasil dihapus',
                'data' => null
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus jadwal penjemputan: ' . $e->getMessage()
            ], 500);
        }
    }
}

