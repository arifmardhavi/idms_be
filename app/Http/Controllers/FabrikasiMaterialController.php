<?php

namespace App\Http\Controllers;

use App\Models\FabrikasiMaterial;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class FabrikasiMaterialController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $fabrikasi_material = FabrikasiMaterial::orderBy('id', 'desc')->get();
        return response()->json([
            'success' => true,
            'message' => 'Fabrikasi Material retrieved successfully.',
            'data' => $fabrikasi_material,
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'readiness_material_id' => 'required|exists:readiness_materials,id',
            'description' => 'required|string',
            'target_date' => 'required|date',
            'status' => 'nullable|integer|in:0,1,2,3', // 0: hijau, 1: biru, 2: kuning, 3: merah
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi gagal',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validatedData = $validator->validated();

        try {
            $fabrikasi_material = FabrikasiMaterial::create($validatedData);

            return response()->json([
                'success' => true,
                'message' => 'Fabrikasi Material created successfully.',
                'data' => $fabrikasi_material,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create Fabrikasi Material.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $fabrikasi_material = FabrikasiMaterial::find($id);
        if (!$fabrikasi_material) {
            return response()->json([
                'success' => false,
                'message' => 'Fabrikasi Material not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Fabrikasi Material retrieved successfully.',
            'data' => $fabrikasi_material,
        ], 200);
    }

    public function showByReadiness(string $id)
    {
        $fabrikasi_material = FabrikasiMaterial::where('readiness_material_id', $id)->orderby('id', 'desc')->get();

        if (!$fabrikasi_material) {
            return response()->json([
                'success' => false,
                'message' => 'Fabrikasi Material not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Fabrikasi Material retrieved successfully.',
            'data' => $fabrikasi_material,
        ], 200);
    }
    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $fabrikasi_material = FabrikasiMaterial::find($id);
        if (!$fabrikasi_material) {
            return response()->json([
                'success' => false,
                'message' => 'Fabrikasi Material not found.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'readiness_material_id' => 'sometimes|exists:readiness_materials,id',
            'description' => 'sometimes|string',
            'target_date' => 'sometimes|date',
            'status' => 'nullable|integer|in:0,1,2,3', // 0: hijau, 1: biru, 2: kuning, 3: merah
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi gagal',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validatedData = $validator->validated();

        try {
            $fabrikasi_material->update($validatedData);

            return response()->json([
                'success' => true,
                'message' => 'Fabrikasi Material updated successfully.',
                'data' => $fabrikasi_material,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update Fabrikasi Material.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $fabrikasi_material = FabrikasiMaterial::find($id);
        if (!$fabrikasi_material) {
            return response()->json([
                'success' => false,
                'message' => 'Fabrikasi Material not found.',
            ], 404);
        }

        try {
            $fabrikasi_material->delete();

            return response()->json([
                'success' => true,
                'message' => 'Fabrikasi Material deleted successfully.',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete Fabrikasi Material.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
