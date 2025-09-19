<?php

namespace App\Http\Controllers;

use App\Models\PoMaterial;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PoMaterialController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $po_material = PoMaterial::orderBy('id', 'desc')->get();
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
            'readiness_material_id' => 'required|exists:readiness_materials,id',
            'no_po' => 'required|integer',
            'po_file' => 'required|file',
            'delivery_date' => 'required|date',
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
            if($request->hasFile('po_file')){
                $file = $request->file('po_file');
                $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $extension = $file->getClientOriginalExtension();
                $dateNow = date('dmY');
                $version = 0;
                $filename = $originalName . '_' . $dateNow . '_' . $version . '.' . $extension;
                while (file_exists(public_path("readiness_ta/material/po/" . $filename))) {
                    $version++;
                    $filename = $originalName . '_' . $dateNow . '_' . $version . '.' . $extension;
                }
                $path = $file->move(public_path('readiness_ta/material/po'), $filename);
                if (!$path) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Failed to upload PO file.',
                    ], 500);
                }
                $validatedData['po_file'] = $filename;
            }
            $po_material = PoMaterial::create($validatedData);

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
        $po_material = PoMaterial::with('readiness_material')->find($id);
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
        $po_material = PoMaterial::with(['readiness_material'])->where('readiness_material_id', $id)->orderby('id', 'desc')->get();

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
        $po_material = PoMaterial::find($id);
        if (!$po_material) {
            return response()->json([
                'success' => false,
                'message' => 'PO Material not found.',
            ], 404);
        }
        $validator = Validator::make($request->all(), [
            'readiness_material_id' => 'sometimes|exists:readiness_materials,id',
            'no_po' => 'sometimes|integer',
            'po_file' => 'sometimes|file',
            'delivery_date' => 'sometimes|date',
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
            if($request->hasFile('po_file')){
                $file = $request->file('po_file');
                $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $extension = $file->getClientOriginalExtension();
                $dateNow = date('dmY');
                $version = 0;
                $filename = $originalName . '_' . $dateNow . '_' . $version . '.' . $extension;
                while (file_exists(public_path("readiness_ta/material/po/" . $filename))) {
                    $version++;
                    $filename = $originalName . '_' . $dateNow . '_' . $version . '.' . $extension;
                }
                $path = $file->move(public_path('readiness_ta/material/po'), $filename);
                if (!$path) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Failed to upload PO file.',
                    ], 500);
                }
                if ($po_material->po_file && file_exists(public_path('readiness_ta/material/po/' . $po_material->po_file))) {
                    unlink(public_path('readiness_ta/material/po/' . $po_material->po_file));
                }
                $validatedData['po_file'] = $filename;
            }
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
        $po_material = PoMaterial::find($id);
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
