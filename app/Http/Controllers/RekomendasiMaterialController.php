<?php

namespace App\Http\Controllers;

use App\Models\RekomendasiMaterial;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class RekomendasiMaterialController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $rekomendasi_material = RekomendasiMaterial::with(['readiness_material', 'historical_memorandum'])->orderBy('id', 'desc')->get();

        return response()->json([
            'success' => true,
            'message' => 'Rekomendasi Material retrieved successfully.',
            'data' => $rekomendasi_material,
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'readiness_material_id' => 'required|exists:readiness_materials,id',
            'historical_memorandum_id' => 'nullable|exists:historical_memorandum,id',
            'rekomendasi_file' => 'nullable|file',
            'target_date' => 'required|date',
            'status' => 'nullable|integer|in:0,1,2,3', // 0: biru, 1: hijau, 2: kuning, 3: merah
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi gagal',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validatedData = $validator->validated();
        try{
            if ($request->hasFile('rekomendasi_file')) {
                $file = $request->file('rekomendasi_file');
                $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $extension = $file->getClientOriginalExtension();
                $dateNow = date('dmY');
                $version = 0;
                $filename = $originalName . '_' . $dateNow . '_' . $version . '.' . $extension;
                while (file_exists(public_path("readiness_ta/material/rekomendasi/" . $filename))) {
                    $version++;
                    $filename = $originalName . '_' . $dateNow . '_' . $version . '.' . $extension;
                }
                $path = $file->move(public_path('readiness_ta/material/rekomendasi'), $filename);
                if (!$path) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Rekomendasi failed upload.',
                    ], 422);
                }
                
                $validatedData['rekomendasi_file'] = $filename;
            }

            $rekomendasi_material = RekomendasiMaterial::create($validatedData);

            return response()->json([
                'success' => true,
                'message' => 'Rekomendasi Material created successfully.',
                'data' => $rekomendasi_material,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create Rekomendasi Material.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $rekomendasi_material = RekomendasiMaterial::with(['readiness_material', 'historical_memorandum'])->find($id);

        if (!$rekomendasi_material) {
            return response()->json([
                'success' => false,
                'message' => 'Rekomendasi Material not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Rekomendasi Material retrieved successfully.',
            'data' => $rekomendasi_material,
        ], 200);
    }

    public function showByReadiness(string $id)
    {
        $rekomendasi_material = RekomendasiMaterial::with(['readiness_material', 'historical_memorandum'])->where('readiness_material_id', $id)->orderby('id', 'desc')->get();

        if (!$rekomendasi_material) {
            return response()->json([
                'success' => false,
                'message' => 'Rekomendasi Material not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Rekomendasi Material retrieved successfully.',
            'data' => $rekomendasi_material,
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $rekomendasi_material = RekomendasiMaterial::find($id);

        if (!$rekomendasi_material) {
            return response()->json([
                'success' => false,
                'message' => 'Rekomendasi Material not found.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'readiness_material_id' => 'sometimes|exists:readiness_materials,id',
            'historical_memorandum_id' => 'nullable|exists:historical_memorandum,id',
            'rekomendasi_file' => 'nullable|file',
            'target_date' => 'sometimes|date',
            'status' => 'sometimes|integer|in:0,1,2,3', // 0: hijau, 1: biru, 2: kuning, 3: merah
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi gagal',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validatedData = $validator->validated();

        try {
            if ($request->hasFile('rekomendasi_file')) {
                $file = $request->file('rekomendasi_file');
                $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $extension = $file->getClientOriginalExtension();
                $dateNow = date('dmY');
                $version = 0;
                $filename = $originalName . '_' . $dateNow . '_' . $version . '.' . $extension;
                while (file_exists(public_path("readiness_ta/material/rekomendasi/" . $filename))) {
                    $version++;
                    $filename = $originalName . '_' . $dateNow . '_' . $version . '.' . $extension;
                }
                $path = $file->move(public_path('readiness_ta/material/rekomendasi'), $filename);
                if (!$path) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Rekomendasi failed upload.',
                    ], 422);
                }
                
                // Hapus file lama jika ada
                if ($rekomendasi_material->rekomendasi_file && file_exists(public_path("readiness_ta/material/rekomendasi/" . $rekomendasi_material->rekomendasi_file))) {
                    unlink(public_path("readiness_ta/material/rekomendasi/" . $rekomendasi_material->rekomendasi_file));
                }

                $validatedData['rekomendasi_file'] = $filename;
                $validatedData['historical_memorandum_id'] = null;
            }

            if ($request->filled('historical_memorandum_id')) {
                if ($rekomendasi_material->rekomendasi_file && file_exists(public_path("readiness_ta/material/rekomendasi/" . $rekomendasi_material->rekomendasi_file))) {
                    unlink(public_path("readiness_ta/material/rekomendasi/" . $rekomendasi_material->rekomendasi_file));
                }

                // Set data: historical id aktif, file dihapus
                $validatedData['rekomendasi_file'] = null;
            }

            $rekomendasi_material->update($validatedData);

            return response()->json([
                'success' => true,
                'message' => 'Rekomendasi Material updated successfully.',
                'data' => $rekomendasi_material,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update Rekomendasi Material.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $rekomendasi_material = RekomendasiMaterial::find($id);

        if (!$rekomendasi_material) {
            return response()->json([
                'success' => false,
                'message' => 'Rekomendasi Material not found.',
            ], 404);
        }

        try {
            // Hapus file jika ada
            if ($rekomendasi_material->file && file_exists(public_path("readiness_ta/material/rekomendasi/" . $rekomendasi_material->file))) {
                unlink(public_path("readiness_ta/material/rekomendasi/" . $rekomendasi_material->file));
            }

            $rekomendasi_material->delete();

            return response()->json([
                'success' => true,
                'message' => 'Rekomendasi Material deleted successfully.',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete Rekomendasi Material.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }
}
