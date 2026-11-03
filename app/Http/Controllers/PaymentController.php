<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Exception;

class PaymentController extends Controller
{
    /**
     * Anggota 3: Display a listing of payments.
     */
    public function index()
    {
        try {
            $payments = Payment::with(['user', 'laundryOrder'])->latest()->get();

            return response()->json([
                'success' => true,
                'message' => 'Daftar pembayaran berhasil diambil',
                'data' => $payments
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data pembayaran: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Anggota 3: Store a newly created payment.
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'user_id' => 'required|exists:users,id',
                'laundry_order_id' => 'required|exists:laundry_orders,id',
                'amount' => 'required|integer|min:0',
                'method' => 'nullable|string|max:30',
                'payment_status' => 'nullable|string|max:30',
                'paid_at' => 'nullable|date',
            ]);

            $payment = Payment::create([
                'user_id' => $validated['user_id'],
                'laundry_order_id' => $validated['laundry_order_id'],
                'amount' => $validated['amount'],
                'method' => $validated['method'] ?? 'cash',
                'payment_status' => $validated['payment_status'] ?? 'pending',
                'paid_at' => $validated['paid_at'] ?? now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Pembayaran berhasil dicatat',
                'data' => $payment->load(['user', 'laundryOrder'])
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
                'message' => 'Gagal mencatat pembayaran: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Anggota 3: Display the specified payment.
     */
    public function show(string $id)
    {
        try {
            $payment = Payment::with(['user', 'laundryOrder'])->find($id);

            if (!$payment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data pembayaran tidak ditemukan',
                    'data' => null
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Detail pembayaran berhasil diambil',
                'data' => $payment
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil detail pembayaran: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Anggota 3: Update the specified payment (PUT / PATCH).
     */
    public function update(Request $request, string $id)
    {
        try {
            $payment = Payment::find($id);

            if (!$payment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data pembayaran tidak ditemukan',
                    'data' => null
                ], 404);
            }

            $validated = $request->validate([
                'user_id' => 'sometimes|required|exists:users,id',
                'laundry_order_id' => 'sometimes|required|exists:laundry_orders,id',
                'amount' => 'sometimes|required|integer|min:0',
                'method' => 'nullable|string|max:30',
                'payment_status' => 'nullable|string|max:30',
                'paid_at' => 'nullable|date',
            ]);

            $payment->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Pembayaran berhasil diperbarui',
                'data' => $payment->fresh()->load(['user', 'laundryOrder'])
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
                'message' => 'Gagal memperbarui pembayaran: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Anggota 3: Remove the specified payment.
     */
    public function destroy(string $id)
    {
        try {
            $payment = Payment::find($id);

            if (!$payment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data pembayaran tidak ditemukan',
                    'data' => null
                ], 404);
            }

            $payment->delete();

            return response()->json([
                'success' => true,
                'message' => 'Pembayaran berhasil dihapus',
                'data' => null
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus pembayaran: ' . $e->getMessage()
            ], 500);
        }
    }
}

