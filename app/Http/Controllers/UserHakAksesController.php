<?php

namespace App\Http\Controllers;

use App\Models\UserHakAkses;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class UserHakAksesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user_hak_akses = UserHakAkses::with(['user', 'hak_akses'])->get();
        return response()->json([
            'success' => true,
            'message' => 'User hak akses retrieved successfully.',
            'data' => $user_hak_akses,
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'hak_akses' => 'required|array',
            'hak_akses.*' => 'required|exists:hak_akses,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validatedData = $validator->validated();

        UserHakAkses::where('user_id', $validatedData['user_id'])->delete();

        foreach ($validatedData['hak_akses'] as $hakAksesId) {
            UserHakAkses::create([
                'user_id' => $validatedData['user_id'],
                'hak_akses_id' => $hakAksesId,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'User hak akses created successfully.',
            'data' => $validatedData,
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $userHakAkses = UserHakAkses::with(['user', 'hak_akses'])->find($id);
        if (!$userHakAkses) {
            return response()->json([
                'success' => false,
                'message' => 'User hak akses not found.',
            ], 404);
        }
        return response()->json([
            'success' => true,
            'message' => 'User hak akses retrieved successfully.',
            'data' => $userHakAkses,
        ], 200);
    }

    /**
     * Display the specified resource by user.
     */
    public function showByUser(string $id)
    {
        $userHakAkses = UserHakAkses::with(['hak_akses'])->where('user_id', $id)->get();
        if ($userHakAkses->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'User hak akses not found.',
            ], 404);
        }
        return response()->json([
            'success' => true,
            'message' => 'User hak akses retrieved successfully.',
            'data' => $userHakAkses,
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $user_hak_akses = UserHakAkses::find($id);
        if (!$user_hak_akses) {
            return response()->json([
                'success' => false,
                'message' => 'User hak akses not found.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'hak_akses_id' => 'required|exists:hak_akses,id',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validatedData = $validator->validated();
        $user_hak_akses->update($validatedData);
        return response()->json([
            'success' => true,
            'message' => 'User hak akses updated successfully.',
            'data' => $user_hak_akses,
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $user_hak_akses = UserHakAkses::find($id);
        if (!$user_hak_akses) {
            return response()->json([
                'success' => false,
                'message' => 'User hak akses not found.',
            ], 404);
        }
        $user_hak_akses->delete();
        return response()->json([
            'success' => true,
            'message' => 'User hak akses deleted successfully.',
        ], 200);
    }
}
