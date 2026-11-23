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
                'user_id' => 'nullable|exists:users,id',
                'laundry_order_id' => 'nullable|exists:laundry_orders,id',
                'status' => 'required|string|max:30',
                'note' => 'nullable|string',
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

            $history = OrderStatusHistory::create([
                'user_id' => $userId,
                'laundry_order_id' => $orderId,
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

            $authUser = $request->user();
            if ($authUser && !$authUser->isAdmin() && (int) $history->user_id !== (int) $authUser->id) {
                return response()->json([
                    'success' => false,
                    'message' => "Akses ditolak (Forbidden). Role 'user' hanya diizinkan mengubah riwayat status pesanan miliknya sendiri."
                ], 403);
            }

            $validated = $request->validate([
                'user_id' => 'sometimes|required|exists:users,id',
                'laundry_order_id' => 'sometimes|required|exists:laundry_orders,id',
                'status' => 'sometimes|required|string|max:30',
                'note' => 'nullable|string',
            ]);

            if ($authUser && !$authUser->isAdmin()) {
                unset($validated['user_id']);
            }

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

