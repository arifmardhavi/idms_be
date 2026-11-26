<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserLoginResource;
use App\Models\User;
use App\Models\UserHakAkses;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    public function register(Request $request) {
        $validator = Validator::make($request->all(), [
            'fullname' => 'required|string|max:255',
            'email' => 'required|string|email|unique:users,email',
            'password' => 'required|string|min:6',
            'level_user' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors.',
                'errors' => $validator->errors(),
                'data' => null
            ], 422);
        }

        $user = User::create([
            'fullname' => $request->fullname,
            'email' => $request->email,
            'password' => $request->password, // Password auto-hashed in model
            'level_user' => $request->level_user,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'User created successfully.',
            'data' => $user,
        ], 201);
    }

    public function login(Request $request)
    {
        $credentials = $request->only('username', 'password');

        // Cek apakah username terdaftar
        $user = User::where('username', $credentials['username'])->first();
        if (!$user) {
            return response()->json([
                'success' => false,
                'error' => 'username',
                'message' => 'Username tidak terdaftar.',
                'data' => null
            ], 404);
        }

        // Cek status user
        if ($user->status != 1) {
            return response()->json([
                'success' => false,
                'error' => 'status',
                'message' => 'Akun tidak aktif.',
                'data' => null
            ], 403);
        }

        // Cek password
        if (!Hash::check($credentials['password'], $user->password)) {
            return response()->json([
                'success' => false,
                'error' => 'password',
                'message' => 'Password salah.',
                'data' => null
            ], 401);
        }

        // Buat token
        $token = JWTAuth::fromUser($user);

        // add hak akses to user
        $data = UserHakAkses::with('hak_akses')
            ->where('user_id', $user->id)
            ->get();

        $hakAksesList = $data
            ->pluck('hak_akses.hak_akses')
            ->filter()
            ->values()
            ->toArray();

        // Username yang dapat akses semua (sumber tunggal: config/file_access.php)
        if (in_array($user->username, config('file_access.superusers'), true)) {
            $hakAksesList[] = '*';
        }

        $user->hak_akses_list = $hakAksesList;

        activity()->log('login', 'Auth', [
            'userId' => $user->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Login berhasil.',
            'data' => [
                'token' => $token,
                'user' => new UserLoginResource($user),
            ],
        ])->withCookie($this->fileAccessCookie($token));
    }

    /**
     * Cookie ini hanya dipakai untuk link file yang dibuka langsung oleh browser.
     * Link biasa tidak mengirim header Authorization, sehingga JWT harus ikut
     * sebagai cookie supaya middleware file.access bisa memvalidasi login.
     */
    private function fileAccessCookie(string $token)
    {
        return cookie()->make(
            config('file_access.cookie'),
            $token,
            (int) config('jwt.ttl'),
            null,
            null,
            (bool) config('file_access.cookie_secure'),
            true,
            false,
            config('file_access.cookie_samesite')
        );
    }


    public function me()
    {
        return response()->json([
            'success' => true,
            'message' => 'User data retrieved successfully.',
            'data' => auth()->user(),
        ]);
    }

    public function refresh()
    {
        return $this->respondWithToken(auth()->refresh());
    }

    protected function respondWithToken($token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * 30
        ])->withCookie($this->fileAccessCookie($token));
    }

    public function logout()
    {
        $user = auth()->user();

        JWTAuth::invalidate(JWTAuth::getToken());

        if ($user) {
            activity()->log('logout', 'Auth', [
                'userId' => $user->id,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Logout berhasil.',
            'data' => null
        ], 200)->withCookie(cookie()->forget(config('file_access.cookie')));
    }
}

