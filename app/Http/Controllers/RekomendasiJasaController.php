<?php

namespace App\Http\Controllers;

use App\Models\RekomendasiJasa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class RekomendasiJasaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $rekomendasi_jasa = RekomendasiJasa::with(['readiness_jasa', 'historical_memorandum'])->orderBy('id', 'desc')->get();

        return response()->json([
            'success' => true,
            'message' => 'Rekomendasi jasa retrieved successfully.',
            'data' => $rekomendasi_jasa,
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'readiness_jasa_id' => 'required|exists:readiness_jasas,id',
            'historical_memorandum_id' => 'nullable|exists:historical_memorandum,id',
            'rekomendasi_file' => 'nullable|file',
            'target_date' => 'nullable|date',
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
                while (file_exists(public_path("readiness_ta/jasa/rekomendasi/" . $filename))) {
                    $version++;
                    $filename = $originalName . '_' . $dateNow . '_' . $version . '.' . $extension;
                }
                $path = $file->move(public_path('readiness_ta/jasa/rekomendasi'), $filename);
                if (!$path) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Rekomendasi failed upload.',
                    ], 422);
                }
                
                $validatedData['rekomendasi_file'] = $filename;
            }

            $rekomendasi_jasa = RekomendasiJasa::create($validatedData);

            return response()->json([
                'success' => true,
                'message' => 'Rekomendasi jasa created successfully.',
                'data' => $rekomendasi_jasa,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create Rekomendasi jasa.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $rekomendasi_jasa = RekomendasiJasa::with(['readiness_jasa', 'historical_memorandum'])->find($id);

        if (!$rekomendasi_jasa) {
            return response()->json([
                'success' => false,
                'message' => 'Rekomendasi jasa not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Rekomendasi jasa retrieved successfully.',
            'data' => $rekomendasi_jasa,
        ], 200);
    }

    public function showByReadiness(string $id)
    {
        $rekomendasi_jasa = RekomendasiJasa::with(['readiness_jasa', 'historical_memorandum'])->where('readiness_jasa_id', $id)->orderby('id', 'desc')->get();

        if (!$rekomendasi_jasa) {
            return response()->json([
                'success' => false,
                'message' => 'Rekomendasi jasa not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Rekomendasi jasa retrieved successfully.',
            'data' => $rekomendasi_jasa,
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $rekomendasi_jasa = RekomendasiJasa::find($id);

        if (!$rekomendasi_jasa) {
            return response()->json([
                'success' => false,
                'message' => 'Rekomendasi jasa not found.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'readiness_jasa_id' => 'sometimes|exists:readiness_jasas,id',
            'historical_memorandum_id' => 'nullable|exists:historical_memorandum,id',
            'rekomendasi_file' => 'sometimes|file',
            'target_date' => 'sometimes|nullable|date',
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
                while (file_exists(public_path("readiness_ta/jasa/rekomendasi/" . $filename))) {
                    $version++;
                    $filename = $originalName . '_' . $dateNow . '_' . $version . '.' . $extension;
                }
                $path = $file->move(public_path('readiness_ta/jasa/rekomendasi'), $filename);
                if (!$path) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Rekomendasi failed upload.',
                    ], 422);
                }
                
                // Hapus file lama jika ada
                if ($rekomendasi_jasa->rekomendasi_file && file_exists(public_path("readiness_ta/jasa/rekomendasi/" . $rekomendasi_jasa->rekomendasi_file))) {
                    unlink(public_path("readiness_ta/jasa/rekomendasi/" . $rekomendasi_jasa->rekomendasi_file));
                }

                $validatedData['rekomendasi_file'] = $filename;
                $validatedData['historical_memorandum_id'] = null;
            }

            if ($request->filled('historical_memorandum_id')) {
                if ($rekomendasi_jasa->rekomendasi_file && file_exists(public_path("readiness_ta/jasa/rekomendasi/" . $rekomendasi_jasa->rekomendasi_file))) {
                    unlink(public_path("readiness_ta/jasa/rekomendasi/" . $rekomendasi_jasa->rekomendasi_file));
                }

                // Set data: historical id aktif, file dihapus
                $validatedData['rekomendasi_file'] = null;
            }

            $rekomendasi_jasa->update($validatedData);

            return response()->json([
                'success' => true,
                'message' => 'Rekomendasi jasa updated successfully.',
                'data' => $rekomendasi_jasa,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update Rekomendasi jasa.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $rekomendasi_jasa = RekomendasiJasa::find($id);

        if (!$rekomendasi_jasa) {
            return response()->json([
                'success' => false,
                'message' => 'Rekomendasi jasa not found.',
            ], 404);
        }

        try {
            // Hapus file jika ada
            if ($rekomendasi_jasa->rekomendasi_file && file_exists(public_path("readiness_ta/jasa/rekomendasi/" . $rekomendasi_jasa->rekomendasi_file))) {
                unlink(public_path("readiness_ta/jasa/rekomendasi/" . $rekomendasi_jasa->rekomendasi_file));
            }

            $rekomendasi_jasa->delete();

            return response()->json([
                'success' => true,
                'message' => 'Rekomendasi jasa deleted successfully.',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete Rekomendasi jasa.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }
}
