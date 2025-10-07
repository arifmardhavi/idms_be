<?php

namespace App\Http\Controllers;

use App\Models\NotifMaterial;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class NotifMaterialController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $notif_material = NotifMaterial::orderBy('id', 'desc')->get();
        return response()->json([
            'success' => true,
            'message' => 'Notif Material retrieved successfully.',
            'data' => $notif_material,
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'readiness_material_id' => 'required|exists:readiness_materials,id',
            'no_notif' => 'nullable|integer',
            'target_date' => 'nullable|date',
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
            $notif_material = NotifMaterial::create($validatedData);

            return response()->json([
                'success' => true,
                'message' => 'Notif Material created successfully.',
                'data' => $notif_material,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create Notif Material.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $notif_material = NotifMaterial::find($id);
        if (!$notif_material) {
            return response()->json([
                'success' => false,
                'message' => 'Notif Material not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Notif Material retrieved successfully.',
            'data' => $notif_material,
        ], 200);
    }

    public function showByReadiness(string $id)
    {
        $notif_material = NotifMaterial::with(['readiness_material'])->where('readiness_material_id', $id)->orderby('id', 'desc')->get();

        if (!$notif_material) {
            return response()->json([
                'success' => false,
                'message' => 'Notif Material not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Notif Material retrieved successfully.',
            'data' => $notif_material,
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $notif_material = NotifMaterial::find($id);

        if (!$notif_material) {
            return response()->json([
                'success' => false,
                'message' => 'Notif Material not found.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'readiness_material_id' => 'sometimes|exists:readiness_materials,id',
            'no_notif' => 'sometimes|integer',
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
            $notif_material->update($validatedData);

            return response()->json([
                'success' => true,
                'message' => 'Notif Material updated successfully.',
                'data' => $notif_material,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update Notif Material.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $notif_material = NotifMaterial::find($id);

        if (!$notif_material) {
            return response()->json([
                'success' => false,
                'message' => 'Notif Material not found.',
            ], 404);
        }

        try {
            $notif_material->delete();

            return response()->json([
                'success' => true,
                'message' => 'Notif Material deleted successfully.',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete Notif Material.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
