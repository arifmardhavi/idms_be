<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    /**
     * Display a listing of users.
     */
    public function index()
    {
        $users = User::with('contracts:id')->orderBy('id', 'desc')->get();
        $data = $users->map(function ($user) {
            return [
                'id' => $user->id,
                'fullname' => $user->fullname,
                'email' => $user->email,
                'username' => $user->username,
                'level_user' => $user->level_user,
                'status' => $user->status,
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
                'contracts' => $user->contracts->pluck('id')->toArray(), // hanya ambil ID dari kontrak
            ];
        });
        return response()->json([
            'success' => true,
            'message' => 'Users retrieved successfully.',
            'data' => $data,
        ], 200);
    }

    /**
     * Store a newly created user in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'fullname' => 'required|string|max:255',
            'email' => 'required|string|email|unique:users,email',
            'username' => 'required|string|unique:users,username',
            'password' => 'required|string|min:6',
            'level_user' => 'required|numeric|in:1,2,3,4,5', // 1: Admin, 2: User, 3: Vendor, 4: viewer all, 5: viewer
            'status' => 'required|in:0,1', // 0: Nonaktif, 1: Aktif
            'contract_id' => 'nullable|array|required_if:level_user,3', // support array
            'contract_id.*' => 'exists:contracts,id', // validasi setiap ID kontrak
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validatedData = $validator->validated();
         // Ambil contract_id dari request jika ada
        $contractIds = $validatedData['contract_id'] ?? [];

        // Hapus contract_id dari array utama agar tidak dimasukkan ke kolom users
        unset($validatedData['contract_id']);

        $user = User::create($validatedData);
        // Hubungkan dengan kontrak (jika ada)
        if (!empty($contractIds)) {
            $user->contracts()->attach($contractIds); // bisa juga pakai sync()
        }

        return response()->json([
            'success' => true,
            'message' => 'User created successfully.',
            'data' => $user->load('contracts'),
        ], 201);
    }

    /**
     * Display the specified user.
     */
    public function show($id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'User retrieved successfully.',
            'data' => $user,
        ], 200);
    }

    /**
     * Update the specified user in storage.
     */
    public function update(Request $request, $id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'fullname' => 'sometimes|string|max:255',
            'email' => 'sometimes|string|email|unique:users,email,' . $id,
            'username' => 'sometimes|string|unique:users,username,' . $id,
            'password' => 'nullable|string|min:6',
            'level_user' => 'sometimes|in:1,2,3,4,5',
            'status' => 'sometimes|in:0,1',
            'contract_id' => 'nullable|string|required_if:level_user,3',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validatedData = $validator->validated();

        // Ambil contract_id yang sudah divalidasi
        $contractIds = explode(',', $validatedData['contract_id']) ?? [];

        unset($validatedData['contract_id']); // Hapus dari mass update

        // Simpan level_user lama untuk membandingkan perubahan
        $oldLevelUser = $user->level_user;
        $newLevelUser = $validatedData['level_user'] ?? $oldLevelUser;

        // Update user
        $user->update($validatedData);

        // Tangani kontrak berdasarkan perubahan level_user
        if ($newLevelUser == 3) {
            // Jika vendor, sinkronkan kontrak (boleh kosong)
            $user->contracts()->sync($contractIds);
        } elseif ($oldLevelUser == 3 && $newLevelUser != 3) {   
            // Jika sebelumnya vendor, dan sekarang bukan vendor, hapus kontrak
            $user->contracts()->sync([]);
        }
        // Jika sebelumnya dan sekarang bukan vendor, tidak perlu apa-apa

        return response()->json([
            'success' => true,
            'message' => 'User updated successfully.',
            'data' => $user->load('contracts:id'),
        ], 200);
    }



    /**
     * Remove the specified user from storage.
     */
    public function destroy($id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found.',
            ], 404);
        }

        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'User deleted successfully.',
        ], 200);
    }

    function nonactive($id) {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'user not found.',
            ], 404);
        }

        try {
            $user->status = 0;
            $user->save();

            return response()->json([
                'success' => true,
                'message' => 'user nonaktif successfully.',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to nonaktif user.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }
}
