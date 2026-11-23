<?php

namespace App\Http\Controllers;

use App\Models\ServiceType;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Exception;

class ServiceTypeController extends Controller
{
    /**
     * Public Endpoint: Menampilkan daftar layanan laundry aktif tanpa perlu login.
     */
    public function publicIndex()
    {
        try {
            $serviceTypes = ServiceType::where('is_active', true)
                ->select(['id', 'name', 'price_per_kg', 'estimated_hours', 'is_active'])
                ->orderBy('id')
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Daftar katalog layanan publik berhasil diambil',
                'data' => $serviceTypes
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil katalog layanan publik: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Anggota 1: Display a listing of service types.
     */
    public function index()
    {
        try {
            $serviceTypes = ServiceType::with('user')->latest()->get();

            return response()->json([
                'success' => true,
                'message' => 'Daftar jenis layanan berhasil diambil',
                'data' => $serviceTypes
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data jenis layanan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Anggota 1: Store a newly created service type.
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'user_id' => 'nullable|exists:users,id',
                'name' => 'required|string|max:100',
                'price_per_kg' => 'required|integer|min:0',
                'estimated_hours' => 'nullable|integer|min:1',
                'is_active' => 'nullable|boolean',
            ]);

            $authUser = $request->user();
            $userId = ($authUser && $authUser->isAdmin() && isset($validated['user_id']))
                ? $validated['user_id']
                : ($authUser?->id ?? \App\Models\User::first()?->id ?? 1);

            $serviceType = ServiceType::create([
                'user_id' => $userId,
                'name' => $validated['name'],
                'price_per_kg' => $validated['price_per_kg'],
                'estimated_hours' => $validated['estimated_hours'] ?? 24,
                'is_active' => $validated['is_active'] ?? true,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Jenis layanan berhasil ditambahkan',
                'data' => $serviceType->load('user')
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
                'message' => 'Gagal menambahkan jenis layanan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Anggota 1: Display the specified service type.
     */
    public function show(string $id)
    {
        try {
            $serviceType = ServiceType::with(['user', 'laundryOrders'])->find($id);

            if (!$serviceType) {
                return response()->json([
                    'success' => false,
                    'message' => 'Jenis layanan tidak ditemukan',
                    'data' => null
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Detail jenis layanan berhasil diambil',
                'data' => $serviceType
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil detail jenis layanan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Anggota 1: Update the specified service type (PUT / PATCH).
     */
    public function update(Request $request, string $id)
    {
        try {
            $serviceType = ServiceType::find($id);

            if (!$serviceType) {
                return response()->json([
                    'success' => false,
                    'message' => 'Jenis layanan tidak ditemukan',
                    'data' => null
                ], 404);
            }

            $authUser = $request->user();
            if ($authUser && !$authUser->isAdmin() && (int) $serviceType->user_id !== (int) $authUser->id) {
                return response()->json([
                    'success' => false,
                    'message' => "Akses ditolak (Forbidden). Role 'user' hanya diizinkan mengubah data jenis layanan miliknya sendiri."
                ], 403);
            }

            $validated = $request->validate([
                'user_id' => 'sometimes|required|exists:users,id',
                'name' => 'sometimes|required|string|max:100',
                'price_per_kg' => 'sometimes|required|integer|min:0',
                'estimated_hours' => 'nullable|integer|min:1',
                'is_active' => 'nullable|boolean',
            ]);

            if ($authUser && !$authUser->isAdmin()) {
                unset($validated['user_id']);
            }

            $serviceType->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Jenis layanan berhasil diperbarui',
                'data' => $serviceType->fresh()->load('user')
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
                'message' => 'Gagal memperbarui jenis layanan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Anggota 1: Remove the specified service type.
     */
    public function destroy(string $id)
    {
        try {
            $serviceType = ServiceType::find($id);

            if (!$serviceType) {
                return response()->json([
                    'success' => false,
                    'message' => 'Jenis layanan tidak ditemukan',
                    'data' => null
                ], 404);
            }

            $serviceType->delete();

            return response()->json([
                'success' => true,
                'message' => 'Jenis layanan berhasil dihapus',
                'data' => null
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus jenis layanan: ' . $e->getMessage()
            ], 500);
        }
    }
}

