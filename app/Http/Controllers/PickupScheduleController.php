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
                'user_id' => 'required|exists:users,id',
                'laundry_order_id' => 'required|exists:laundry_orders,id',
                'scheduled_at' => 'required|date',
                'type' => 'nullable|string|max:20',
                'address' => 'required|string|max:255',
                'status' => 'nullable|string|max:30',
            ]);

            $schedule = PickupSchedule::create([
                'user_id' => $validated['user_id'],
                'laundry_order_id' => $validated['laundry_order_id'],
                'scheduled_at' => $validated['scheduled_at'],
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

            $validated = $request->validate([
                'user_id' => 'sometimes|required|exists:users,id',
                'laundry_order_id' => 'sometimes|required|exists:laundry_orders,id',
                'scheduled_at' => 'sometimes|required|date',
                'type' => 'nullable|string|max:20',
                'address' => 'sometimes|required|string|max:255',
                'status' => 'nullable|string|max:30',
            ]);

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

