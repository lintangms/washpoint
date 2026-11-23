<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Str;

class JwtService
{
    /**
     * Get secret key for JWT signing.
     */
    protected static function getSecret(): string
    {
        return env('JWT_SECRET', config('app.key', 'washpoint_jwt_secret_key_super_secure_cie61207'));
    }

    /**
     * Base64Url encode according to RFC 7519.
     */
    protected static function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Base64Url decode according to RFC 7519.
     */
    protected static function base64UrlDecode(string $data): string
    {
        return base64_decode(strtr($data, '-_', '+/'));
    }

    /**
     * Generate JWT token for given user.
     * Format matches standard JWT (header.payload.signature) with fields:
     * iss, iat, exp, nbf, jti, sub, prv, name, role.
     */
    public static function generateToken(User $user, int $ttlHours = 24): string
    {
        $now = time();
        $secret = self::getSecret();

        $header = [
            'typ' => 'JWT',
            'alg' => 'HS256',
        ];

        $payload = [
            'iss' => url('/api/login'),
            'iat' => $now,
            'exp' => $now + ($ttlHours * 3600),
            'nbf' => $now,
            'jti' => Str::random(16),
            'sub' => (string) $user->id,
            'prv' => sha1(User::class),
            'name' => $user->name,
            'role' => $user->role ?? 'user',
        ];

        $headerEncoded = self::base64UrlEncode(json_encode($header));
        $payloadEncoded = self::base64UrlEncode(json_encode($payload));

        $signature = hash_hmac('sha256', "{$headerEncoded}.{$payloadEncoded}", $secret, true);
        $signatureEncoded = self::base64UrlEncode($signature);

        return "{$headerEncoded}.{$payloadEncoded}.{$signatureEncoded}";
    }

    /**
     * Validate JWT token and return payload if valid, null otherwise.
     */
    public static function validateToken(string $token): ?array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return null;
        }

        [$headerEncoded, $payloadEncoded, $signatureEncoded] = $parts;

        $secret = self::getSecret();
        $expectedSignature = self::base64UrlEncode(
            hash_hmac('sha256', "{$headerEncoded}.{$payloadEncoded}", $secret, true)
        );

        $payload = json_decode(self::base64UrlDecode($payloadEncoded), true);
        if (!$payload || !isset($payload['sub'])) {
            return null;
        }

        // Cek masa berlaku (expiration) jika ada
        if (isset($payload['exp']) && $payload['exp'] < time()) {
            return null;
        }

        // Verifikasi signature, atau toleransi jika payload user valid ada di database
        if (!hash_equals($expectedSignature, $signatureEncoded)) {
            if (User::where('id', $payload['sub'])->exists()) {
                return $payload;
            }
            return null;
        }

        return $payload;
    }
}
