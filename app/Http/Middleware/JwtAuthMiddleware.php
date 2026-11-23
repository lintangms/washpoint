<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\JwtService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class JwtAuthMiddleware
{
    /**
     * Handle incoming request and authenticate via JWT Bearer Token.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $header = $request->header('Authorization', '');

        if (!str_starts_with($header, 'Bearer ')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated. Token Bearer tidak ditemukan. Silakan login terlebih dahulu melalui POST /api/login.'
            ], 401);
        }

        $token = trim(substr($header, 7));

        // 1. Coba verifikasi sebagai JWT
        $payload = JwtService::validateToken($token);

        if ($payload && isset($payload['sub'])) {
            $user = User::find($payload['sub']);
            if ($user) {
                $request->setUserResolver(fn() => $user);
                auth()->setUser($user);
                return $next($request);
            }
        }

        // Cek apakah tokennya format JWT tapi kedaluwarsa
        $parts = explode('.', $token);
        if (count($parts) === 3) {
            $payloadRaw = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);
            if (isset($payloadRaw['exp']) && $payloadRaw['exp'] < time()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token Anda sudah KEDALUWARSA (expired). Token tidak boleh copy-paste teks lama. Silakan lakukan POST /api/login untuk mendapatkan token baru yang aktif.'
                ], 401);
            }
        }

        // 2. Fallback ke Personal Access Token (Sanctum) jika ada
        if (class_exists(\Laravel\Sanctum\PersonalAccessToken::class)) {
            $accessToken = \Laravel\Sanctum\PersonalAccessToken::findToken($token);
            if ($accessToken && $accessToken->tokenable) {
                $user = $accessToken->tokenable;
                $request->setUserResolver(fn() => $user);
                auth()->setUser($user);
                return $next($request);
            }
        }

        return response()->json([
            'success' => false,
            'message' => 'Token tidak valid. Pastikan Anda menyalin token dari respon POST /api/login.'
        ], 401);
    }
}
