<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request for specific role authorization.
     * Contoh: middleware('role:admin') atau middleware('role:admin,user')
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated. Silakan login terlebih dahulu.'
            ], 401);
        }

        if (!in_array($user->role, $roles)) {
            return response()->json([
                'success' => false,
                'message' => "Akses ditolak (Forbidden). Anda tidak memiliki wewenang untuk mengakses sumber daya ini. Diperlukan role: " . implode(', ', $roles)
            ], 403);
        }

        return $next($request);
    }
}
