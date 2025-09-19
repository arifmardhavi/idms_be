<?php

namespace App\Http\Controllers;

use App\Models\DeliveryMaterial;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DeliveryMaterialController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $delivery_material = DeliveryMaterial::orderBy('id', 'desc')->get();
        return response()->json([
            'success' => true,
            'message' => 'Delivery Material retrieved successfully.',
            'data' => $delivery_material,
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
            'delivery_file' => 'nullable|file',
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
            if($request->hasFile('delivery_file')){
                $file = $request->file('delivery_file');
                $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $extension = $file->getClientOriginalExtension();
                $dateNow = date('dmY');
                $version = 0;
                $filename = $originalName . '_' . $dateNow . '_' . $version . '.' . $extension;
                while (file_exists(public_path("readiness_ta/material/delivery/" . $filename))) {
                    $version++;
                    $filename = $originalName . '_' . $dateNow . '_' . $version . '.' . $extension;
                }
                $path = $file->move(public_path('readiness_ta/material/delivery'), $filename);
                if (!$path) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Failed to upload Delivery file.',
                    ], 500);
                }
                $validatedData['delivery_file'] = $filename;
            }
            $delivery_material = DeliveryMaterial::create($validatedData);

            return response()->json([
                'success' => true,
                'message' => 'Delivery Material created successfully.',
                'data' => $delivery_material,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Delivery Material created failed.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $delivery_material = DeliveryMaterial::find($id);

        if (!$delivery_material) {
            return response()->json([
                'success' => false,
                'message' => 'Delivery Material not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Delivery Material retrieved successfully.',
            'data' => $delivery_material,
        ], 200);
    }

    public function showByReadiness(string $id)
    {
        $delivery_material = DeliveryMaterial::where('readiness_material_id', $id)->orderby('id', 'desc')->get();

        if (!$delivery_material) {
            return response()->json([
                'success' => false,
                'message' => 'Delivery Material not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Delivery Material retrieved successfully.',
            'data' => $delivery_material,
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $delivery_material = DeliveryMaterial::find($id);
        if (!$delivery_material) {
            return response()->json([
                'success' => false,
                'message' => 'Delivery Material not found.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'readiness_material_id' => 'sometimes|exists:readiness_materials,id',
            'description' => 'sometimes|string',
            'delivery_file' => 'nullable|file',
            'target_date' => '  sometimes|date',
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
            if($request->hasFile('delivery_file')){
                $file = $request->file('delivery_file');
                $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $extension = $file->getClientOriginalExtension();
                $dateNow = date('dmY');
                $version = 0;
                $filename = $originalName . '_' . $dateNow . '_' . $version . '.' . $extension;
                while (file_exists(public_path("readiness_ta/material/delivery/" . $filename))) {
                    $version++;
                    $filename = $originalName . '_' . $dateNow . '_' . $version . '.' . $extension;
                }
                $path = $file->move(public_path('readiness_ta/material/delivery'), $filename);
                if (!$path) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Failed to upload Delivery file.',
                    ], 500);
                }
                if ($delivery_material->delivery_file && file_exists(public_path('readiness_ta/material/delivery/' . $delivery_material->delivery_file))) {
                    unlink(public_path('readiness_ta/material/delivery/' . $delivery_material->delivery_file));
                }
                $validatedData['delivery_file'] = $filename;
            }
            $delivery_material->update($validatedData);

            return response()->json([
                'success' => true,
                'message' => 'Delivery Material updated successfully.',
                'data' => $delivery_material,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update Delivery Material.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $delivery_material = DeliveryMaterial::find($id);
        if (!$delivery_material) {
            return response()->json([
                'success' => false,
                'message' => 'Delivery Material not found.',
            ], 404);
        }

        try {
            if ($delivery_material->delivery_file && file_exists(public_path('readiness_ta/material/delivery/' . $delivery_material->delivery_file))) {
                unlink(public_path('readiness_ta/material/delivery/' . $delivery_material->delivery_file));
            }
            $delivery_material->delete();

            return response()->json([
                'success' => true,
                'message' => 'Delivery Material deleted successfully.',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete Delivery Material.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
