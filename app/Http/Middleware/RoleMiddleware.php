<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, ...$levels)
    {
        // Izinkan semua akses GET tanpa cek level
        if ($request->isMethod('get')) {
            return $next($request);
        }

        // Autentikasi user
        $user = JWTAuth::parseToken()->authenticate();

        // Cek status aktif
        if ($user->status != 1) {
            return response()->json(['error' => 'Akun tidak aktif'], 403);
        }

        // Cek akses berdasarkan level langsung (kecuali GET)
        if (!in_array($user->level_user, $levels)) {
            return response()->json(['error' => 'Tidak memiliki akses'], 403);
        }

        return $next($request);
    }
}
