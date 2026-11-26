<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

class EnsureFileAccess
{
    /**
     * Link file dibuka langsung oleh browser, jadi token bisa datang dari
     * header Authorization (fetch) maupun cookie (navigasi biasa). Keduanya
     * dicoba terpisah: header yang kedaluwarsa tidak boleh menghalangi cookie
     * yang masih valid. Semua kegagalan sengaja jadi 404 agar eksistensi file
     * tidak bisa diketahui pihak yang tidak berhak.
     */
    public function handle(Request $request, Closure $next, ?string $permission = null)
    {
        $tokens = array_filter(
            [$request->bearerToken(), $request->cookie(config('file_access.cookie'))],
            fn ($token) => is_string($token) && $token !== ''
        );

        foreach ($tokens as $token) {
            try {
                $user = JWTAuth::setToken($token)->authenticate();
            } catch (\Throwable $exception) {
                continue;
            }

            if (!$user || $user->status != 1) {
                continue;
            }

            if ($permission !== null && !$this->hasPermission($user, $permission)) {
                continue;
            }

            return $next($request);
        }

        abort(404);
    }

    private function hasPermission($user, string $permission): bool
    {
        if (in_array($user->username, config('file_access.superusers'), true)) {
            return true;
        }

        return $user->hak_akses()
            ->whereHas('hak_akses', function ($query) use ($permission) {
                $query->where('hak_akses', $permission);
            })
            ->exists();
    }
}
