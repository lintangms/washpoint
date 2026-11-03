<?php

namespace App\Http\Controllers;

use App\Models\LaundryOrder;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Exception;

class LaundryOrderController extends Controller
{
    /**
     * Anggota 2: Display a listing of laundry orders.
     */
    public function index()
    {
        try {
            $orders = LaundryOrder::with(['user', 'serviceType'])->latest()->get();

            return response()->json([
                'success' => true,
                'message' => 'Daftar pesanan laundry berhasil diambil',
                'data' => $orders
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data pesanan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Anggota 2: Store a newly created laundry order.
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'user_id' => 'required|exists:users,id',
                'service_type_id' => 'required|exists:service_types,id',
                'customer_name' => 'required|string|max:100',
                'customer_phone' => 'required|string|max:25',
                'weight_kg' => 'required|numeric|min:0.1',
                'total_price' => 'required|integer|min:0',
                'status' => 'nullable|string|max:30',
                'notes' => 'nullable|string',
            ]);

            $order = LaundryOrder::create([
                'user_id' => $validated['user_id'],
                'service_type_id' => $validated['service_type_id'],
                'customer_name' => $validated['customer_name'],
                'customer_phone' => $validated['customer_phone'],
                'weight_kg' => $validated['weight_kg'],
                'total_price' => $validated['total_price'],
                'status' => $validated['status'] ?? 'received',
                'notes' => $validated['notes'] ?? null,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Pesanan laundry berhasil dibuat',
                'data' => $order->load(['user', 'serviceType'])
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
                'message' => 'Gagal membuat pesanan laundry: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Anggota 2: Display the specified laundry order.
     */
    public function show(string $id)
    {
        try {
            $order = LaundryOrder::with([
                'user',
                'serviceType',
                'payments',
                'pickupSchedules',
                'orderStatusHistories'
            ])->find($id);

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pesanan laundry tidak ditemukan',
                    'data' => null
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Detail pesanan laundry berhasil diambil',
                'data' => $order
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil detail pesanan laundry: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Anggota 2: Update the specified laundry order (PUT / PATCH).
     */
    public function update(Request $request, string $id)
    {
        try {
            $order = LaundryOrder::find($id);

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pesanan laundry tidak ditemukan',
                    'data' => null
                ], 404);
            }

            $validated = $request->validate([
                'user_id' => 'sometimes|required|exists:users,id',
                'service_type_id' => 'sometimes|required|exists:service_types,id',
                'customer_name' => 'sometimes|required|string|max:100',
                'customer_phone' => 'sometimes|required|string|max:25',
                'weight_kg' => 'sometimes|required|numeric|min:0.1',
                'total_price' => 'sometimes|required|integer|min:0',
                'status' => 'nullable|string|max:30',
                'notes' => 'nullable|string',
            ]);

            $order->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Pesanan laundry berhasil diperbarui',
                'data' => $order->fresh()->load(['user', 'serviceType'])
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
                'message' => 'Gagal memperbarui pesanan laundry: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Anggota 2: Remove the specified laundry order.
     */
    public function destroy(string $id)
    {
        try {
            $order = LaundryOrder::find($id);

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pesanan laundry tidak ditemukan',
                    'data' => null
                ], 404);
            }

            $order->delete();

            return response()->json([
                'success' => true,
                'message' => 'Pesanan laundry berhasil dihapus',
                'data' => null
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus pesanan laundry: ' . $e->getMessage()
            ], 500);
        }
    }
}

