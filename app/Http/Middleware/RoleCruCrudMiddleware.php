<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleCruCrudMiddleware
{
    /**
     * Handle an incoming request for CRU (user) and CRUD (admin) authorization.
     *
     * Aturan:
     * - Role 'user' hanya dapat melakukan CRU (Create, Read, Update).
     * - Role 'admin' dapat melakukan CRUD (Create, Read, Update, Delete).
     * - Operasi DELETE dilarang untuk role 'user' (403 Forbidden).
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated. Silakan login terlebih dahulu.'
            ], 401);
        }

        // Jika request adalah DELETE, hanya role admin yang diizinkan
        if ($request->isMethod('delete')) {
            if ($user->role !== 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => "Akses ditolak (Forbidden). Role '{$user->role}' hanya memiliki hak akses CRU (Create, Read, Update). Tindakan DELETE hanya dapat dilakukan oleh role 'admin'."
                ], 403);
            }
        }

        return $next($request);
    }
}
