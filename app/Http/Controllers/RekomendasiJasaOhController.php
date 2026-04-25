<?php

namespace App\Http\Controllers;

use App\Helpers\FileHelper;
use App\Models\RekomendasiJasaOh;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class RekomendasiJasaOhController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $rekomendasi_jasa = RekomendasiJasaOh::with(['readiness_jasa_oh', 'historical_memorandum'])->orderBy('id', 'desc')->get();

        return response()->json([
            'success' => true,
            'message' => 'Rekomendasi Jasa OH retrieved successfully.',
            'data' => $rekomendasi_jasa,
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'readiness_jasa_oh_id' => 'required|exists:readiness_jasa_ohs,id',
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
                $validatedData['rekomendasi_file'] = FileHelper::uploadWithVersion($request->file('rekomendasi_file'), 'readiness_oh/jasa/rekomendasi/');
            }

            $rekomendasi_jasa = RekomendasiJasaOh::create($validatedData);

            return response()->json([
                'success' => true,
                'message' => 'Rekomendasi Jasa OH created successfully.',
                'data' => $rekomendasi_jasa,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create Rekomendasi Jasa OH.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $rekomendasi_jasa = RekomendasiJasaOh::with(['readiness_jasa_oh', 'historical_memorandum'])->find($id);

        if (!$rekomendasi_jasa) {
            return response()->json([
                'success' => false,
                'message' => 'Rekomendasi Jasa OH not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Rekomendasi Jasa OH retrieved successfully.',
            'data' => $rekomendasi_jasa,
        ], 200);
    }

    public function showByReadiness(string $id)
    {
        $rekomendasi_jasa = RekomendasiJasaOh::with(['readiness_jasa_oh', 'historical_memorandum'])->where('readiness_jasa_oh_id', $id)->orderby('id', 'desc')->get();

        if (!$rekomendasi_jasa) {
            return response()->json([
                'success' => false,
                'message' => 'Rekomendasi Jasa OH not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Rekomendasi Jasa OH retrieved successfully.',
            'data' => $rekomendasi_jasa,
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $rekomendasi_jasa = RekomendasiJasaOh::find($id);

        if (!$rekomendasi_jasa) {
            return response()->json([
                'success' => false,
                'message' => 'Rekomendasi Jasa OH not found.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'readiness_jasa_oh_id' => 'sometimes|exists:readiness_jasa_ohs,id',
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
                $validatedData['rekomendasi_file'] = FileHelper::uploadWithVersion($request->file('rekomendasi_file'), 'readiness_oh/jasa/rekomendasi/');

                // Hapus file lama jika ada
                if ($rekomendasi_jasa->rekomendasi_file) {
                    FileHelper::deleteFile($rekomendasi_jasa->rekomendasi_file, 'readiness_oh/jasa/rekomendasi/');
                }
            }
                $validatedData['historical_memorandum_id'] = null;

            if ($request->filled('historical_memorandum_id')) {
                if ($rekomendasi_jasa->rekomendasi_file) {
                    FileHelper::deleteFile($rekomendasi_jasa->rekomendasi_file, 'readiness_oh/jasa/rekomendasi/');
                }

                // Set data: historical id aktif, file dihapus
                $validatedData['rekomendasi_file'] = null;
            }

            $rekomendasi_jasa->update($validatedData);

            return response()->json([
                'success' => true,
                'message' => 'Rekomendasi Jasa OH updated successfully.',
                'data' => $rekomendasi_jasa,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update Rekomendasi Jasa OH.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $rekomendasi_jasa = RekomendasiJasaOh::find($id);

        if (!$rekomendasi_jasa) {
            return response()->json([
                'success' => false,
                'message' => 'Rekomendasi Jasa OH not found.',
            ], 404);
        }

        try {
            // Hapus file jika ada
            if ($rekomendasi_jasa->rekomendasi_file) {
                FileHelper::deleteFile($rekomendasi_jasa->rekomendasi_file, 'readiness_oh/jasa/rekomendasi/');
            }

            $rekomendasi_jasa->delete();

            return response()->json([
                'success' => true,
                'message' => 'Rekomendasi Jasa OH deleted successfully.',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete Rekomendasi Jasa OH.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }
}
