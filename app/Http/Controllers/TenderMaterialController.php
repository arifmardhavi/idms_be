<?php

namespace App\Http\Controllers;

use App\Models\TenderMaterial;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TenderMaterialController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $tender_material = TenderMaterial::orderBy('id', 'desc')->get();
        return response()->json([
            'success' => true,
            'message' => 'Tender Material retrieved successfully.',
            'data' => $tender_material,
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
            $tender_material = TenderMaterial::create($validatedData);

            return response()->json([
                'success' => true,
                'message' => 'Tender Material created successfully.',
                'data' => $tender_material,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create Tender Material.',
                'error' => $e->getMessage(),
            ], 500);
        }

    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $tender_material = TenderMaterial::find($id);
        if (!$tender_material) {
            return response()->json([
                'success' => false,
                'message' => 'Tender Material not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Tender Material retrieved successfully.',
            'data' => $tender_material,
        ], 200);
    }

    public function showByReadiness(string $id)
    {
        $tender_material = TenderMaterial::with(['readiness_material'])->where('readiness_material_id', $id)->orderby('id', 'desc')->get();

        if (!$tender_material) {
            return response()->json([
                'success' => false,
                'message' => 'Tender Material not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Tender Material retrieved successfully.',
            'data' => $tender_material,
        ], 200);
        
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $tender_material = TenderMaterial::find($id);
        if (!$tender_material) {
            return response()->json([
                'success' => false,
                'message' => 'Tender Material not found.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'readiness_material_id' => 'sometimes|required|exists:readiness_materials,id',
            'description' => 'sometimes|required|string',
            'target_date' => 'sometimes|required|date',
            'status' => 'sometimes|required|integer|in:0,1,2,3', // 0: hijau, 1: biru, 2: kuning, 3: merah
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi gagal',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validatedData = $validator->validated();

        try {
            $tender_material->update($validatedData);

            return response()->json([
                'success' => true,
                'message' => 'Tender Material updated successfully.',
                'data' => $tender_material,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update Tender Material.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $tender_material = TenderMaterial::find($id);
        if (!$tender_material) {
            return response()->json([
                'success' => false,
                'message' => 'Tender Material not found.',
            ], 404);
        }

        try {
            $tender_material->delete();

            return response()->json([
                'success' => true,
                'message' => 'Tender Material deleted successfully.',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete Tender Material.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
