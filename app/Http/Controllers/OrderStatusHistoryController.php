<?php

namespace App\Http\Controllers;

use App\Models\OrderStatusHistory;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Exception;

class OrderStatusHistoryController extends Controller
{
    /**
     * Anggota 5: Display a listing of order status histories.
     */
    public function index()
    {
        try {
            $histories = OrderStatusHistory::with(['user', 'laundryOrder'])->latest()->get();

            return response()->json([
                'success' => true,
                'message' => 'Daftar riwayat status pesanan berhasil diambil',
                'data' => $histories
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil riwayat status pesanan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Anggota 5: Store a newly created order status history.
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'user_id' => 'required|exists:users,id',
                'laundry_order_id' => 'required|exists:laundry_orders,id',
                'status' => 'required|string|max:30',
                'note' => 'nullable|string',
            ]);

            $history = OrderStatusHistory::create([
                'user_id' => $validated['user_id'],
                'laundry_order_id' => $validated['laundry_order_id'],
                'status' => $validated['status'],
                'note' => $validated['note'] ?? null,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Riwayat status pesanan berhasil ditambahkan',
                'data' => $history->load(['user', 'laundryOrder'])
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
                'message' => 'Gagal menambahkan riwayat status pesanan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Anggota 5: Display the specified order status history.
     */
    public function show(string $id)
    {
        try {
            $history = OrderStatusHistory::with(['user', 'laundryOrder'])->find($id);

            if (!$history) {
                return response()->json([
                    'success' => false,
                    'message' => 'Riwayat status pesanan tidak ditemukan',
                    'data' => null
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Detail riwayat status pesanan berhasil diambil',
                'data' => $history
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil detail riwayat status: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Anggota 5: Update the specified order status history (PUT / PATCH).
     */
    public function update(Request $request, string $id)
    {
        try {
            $history = OrderStatusHistory::find($id);

            if (!$history) {
                return response()->json([
                    'success' => false,
                    'message' => 'Riwayat status pesanan tidak ditemukan',
                    'data' => null
                ], 404);
            }

            $validated = $request->validate([
                'user_id' => 'sometimes|required|exists:users,id',
                'laundry_order_id' => 'sometimes|required|exists:laundry_orders,id',
                'status' => 'sometimes|required|string|max:30',
                'note' => 'nullable|string',
            ]);

            $history->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Riwayat status pesanan berhasil diperbarui',
                'data' => $history->fresh()->load(['user', 'laundryOrder'])
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
                'message' => 'Gagal memperbarui riwayat status: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Anggota 5: Remove the specified order status history.
     */
    public function destroy(string $id)
    {
        try {
            $history = OrderStatusHistory::find($id);

            if (!$history) {
                return response()->json([
                    'success' => false,
                    'message' => 'Riwayat status pesanan tidak ditemukan',
                    'data' => null
                ], 404);
            }

            $history->delete();

            return response()->json([
                'success' => true,
                'message' => 'Riwayat status pesanan berhasil dihapus',
                'data' => null
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus riwayat status: ' . $e->getMessage()
            ], 500);
        }
    }
}

