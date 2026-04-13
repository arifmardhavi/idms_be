<?php

namespace App\Http\Controllers;

use App\Models\HakAkses;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class HakAksesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $hak_akses = HakAkses::with('feature')->get();
        return response()->json([
            'success' => true,
            'message' => 'Hak akses retrieved successfully.',
            'data' => $hak_akses,
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'feature_id' => 'required|exists:features,id',
            'hak_akses' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'data' => $validator->errors(),
            ], 422);
        }
        $validatedData = $validator->validated();
        $hak_akses = HakAkses::create($validatedData);
        return response()->json([
            'success' => true,
            'message' => 'Hak akses created successfully.',
            'data' => $hak_akses,
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $hak_akses = HakAkses::find($id);

        if (!$hak_akses) {
            return response()->json([
                'success' => false,
                'message' => 'Hak akses not found.',
                'data' => null,
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Hak akses retrieved successfully.',
            'data' => $hak_akses,
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $hak_akses = HakAkses::find($id);

        if (!$hak_akses) {
            return response()->json([
                'success' => false,
                'message' => 'Hak akses not found.',
                'data' => null,
            ], 404);
        }
        $validator = Validator::make($request->all(), [
            'feature_id' => 'required|exists:features,id',
            'hak_akses' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'data' => $validator->errors(),
            ], 422);
        }

        $hak_akses->update($request->all());
        return response()->json([
            'success' => true,
            'message' => 'Hak akses updated successfully.',
            'data' => $hak_akses,
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $hak_akses = HakAkses::find($id);

        if (!$hak_akses) {
            return response()->json([
                'success' => false,
                'message' => 'Hak akses not found.',
                'data' => null,
            ], 404);
        }

        $hak_akses->delete();
        return response()->json([
            'success' => true,
            'message' => 'Hak akses deleted successfully.',
            'data' => null,
        ], 200);
    }
}
