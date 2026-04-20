<?php

namespace App\Http\Controllers;

use App\Models\PoMaterialOh;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PoMaterialOhController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $po_material = PoMaterialOh::orderBy('id', 'desc')->get();
        return response()->json([
            'success' => true,
            'message' => 'PO Material retrieved successfully.',
            'data' => $po_material,
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'readiness_material_oh_id' => 'required|exists:readiness_material_ohs,id',
            'contract_new_id' => 'nullable|exists:contract_news,id',
            'no_po' => 'nullable|integer',
            'delivery_date' => 'nullable|date',
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
            $po_material = PoMaterialOh::create($validatedData);

            return response()->json([
                'success' => true,
                'message' => 'PO Material created successfully.',
                'data' => $po_material,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create PO Material.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $po_material = PoMaterialOh::with('readiness_material_oh')->find($id);
        if (!$po_material) {
            return response()->json([
                'success' => false,
                'message' => 'PO Material not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'PO Material retrieved successfully.',
            'data' => $po_material,
        ], 200);
    }

    public function showByReadiness(string $id)
    {
        $po_material = PoMaterialOh::with(['readiness_material_oh'])->where('readiness_material_oh_id', $id)->orderby('id', 'desc')->get();

        if (!$po_material) {
            return response()->json([
                'success' => false,
                'message' => 'PO Material not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'PO Material retrieved successfully.',
            'data' => $po_material,
        ], 200);
    }
    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $po_material = PoMaterialOh::find($id);
        if (!$po_material) {
            return response()->json([
                'success' => false,
                'message' => 'PO Material not found.',
            ], 404);
        }
        $validator = Validator::make($request->all(), [
            'readiness_material_oh_id' => 'sometimes|exists:readiness_material_ohs,id',
            'contract_new_id' => 'sometimes|nullable|exists:contract_news,id',
            'no_po' => 'sometimes|integer',
            'delivery_date' => 'sometimes|date',
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
            $po_material->update($validatedData);
            return response()->json([
                'success' => true,
                'message' => 'PO Material updated successfully.',
                'data' => $po_material,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update PO Material.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $po_material = PoMaterialOh::find($id);
        if (!$po_material) {
            return response()->json([
                'success' => false,
                'message' => 'PO Material not found.',
            ], 404);
        }

        try {
            if ($po_material->po_file && file_exists(public_path('readiness_ta/material/po/' . $po_material->po_file))) {
                unlink(public_path('readiness_ta/material/po/' . $po_material->po_file));
            }
            $po_material->delete();

            return response()->json([
                'success' => true,
                'message' => 'PO Material deleted successfully.',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete PO Material.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
