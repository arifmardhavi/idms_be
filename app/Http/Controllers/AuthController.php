<?php

namespace App\Http\Controllers;

use App\Models\User;
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

        return response()->json([
            'success' => true,
            'message' => 'Login berhasil.',
            'data' => [
                'token' => $token,
                'user' => $user
            ],
        ]);
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
        ]);
    }

    public function logout()
    {
        JWTAuth::invalidate(JWTAuth::getToken());
        return response()->json([
            'success' => true,
            'message' => 'Logout berhasil.',
            'data' => null
        ]);
    }
}

