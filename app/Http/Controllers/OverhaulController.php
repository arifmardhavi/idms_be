<?php

namespace App\Http\Controllers;

use App\Helpers\FileHelper;
use App\Models\Overhaul;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class OverhaulController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $overhaul = Overhaul::with('laporan_inspection', 'historical_memorandum')->get();
        return response()->json([
            'success' => true,
            'message' => 'Overhaul retrieved successfully.',
            'data' => $overhaul,
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'laporan_inspection_id' => 'required|exists:laporan_inspections,id',
            'judul' => 'required|string|max:255',
            'overhaul_date' => 'required|date',
            'historical_memorandum_id' => 'nullable|exists:historical_memorandum,id',
            'laporan_file' => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png|max:204800',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed for Overhaul',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validatedData = $validator->validated();
        try {
            if ($request->hasFile('laporan_file')) {
                $validatedData['laporan_file'] = FileHelper::uploadWithVersion($request->file('laporan_file'), 'laporan_inspection/overhaul');
            }

            $overhaul = Overhaul::create($validatedData);

            return response()->json([
                'success' => true,
                'message' => 'Overhaul created successfully.',
                'data' => $overhaul,
            ], 201);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create Overhaul.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $overhaul = Overhaul::with('laporan_inspection', 'historical_memorandum')->find($id);
        if (!$overhaul) {
            return response()->json([
                'success' => false,
                'message' => 'Overhaul not found.',
            ], 404);
        }
        return response()->json([
            'success' => true,
            'message' => 'Overhaul retrieved successfully.',
            'data' => $overhaul,
        ], 200);
    }
    public function showByLaporanInspection(string $id)
    {
        $overhaul = Overhaul::with('laporan_inspection', 'historical_memorandum')->where('laporan_inspection_id', $id)->get();
        if (!$overhaul) {
            return response()->json([
                'success' => false,
                'message' => 'Overhaul not found.',
            ], 404);
        }
        return response()->json([
            'success' => true,
            'message' => 'Overhaul retrieved successfully.',
            'data' => $overhaul,
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $overhaul = Overhaul::find($id);
        if (!$overhaul) {
            return response()->json([
                'success' => false,
                'message' => 'Overhaul not found.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'judul' => 'required|string|max:255',
            'overhaul_date' => 'required|date',
            'historical_memorandum_id' => 'nullable|exists:historical_memorandum,id',
            'laporan_file' => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png,zip,rar|max:204800',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed for Overhaul',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validatedData = $validator->validated();

        try {
            // Jika historical_memorandum_id diisi, hapus file lama
            if ($request->filled('historical_memorandum_id')) {
                if ($overhaul->laporan_file) {
                    FileHelper::deleteFile($overhaul->laporan_file, 'laporan_inspection/overhaul');
                }
                $validatedData['laporan_file'] = null; // Set null karena pakai memorandum
            }

            // Jika ada file baru diupload
            if ($request->hasFile('laporan_file')) {
                if ($overhaul->laporan_file) {
                    FileHelper::deleteFile($overhaul->laporan_file, 'laporan_inspection/overhaul');
                }

                // Jika ada file, maka hapus relasi historical memorandum
                if ($overhaul->historical_memorandum_id) {
                    $validatedData['historical_memorandum_id'] = null;
                }

                $validatedData['laporan_file'] = FileHelper::uploadWithVersion($request->file('laporan_file'), 'laporan_inspection/overhaul');
            }

            if ($overhaul->update($validatedData)) {
                return response()->json([
                    'success' => true,
                    'message' => 'Overhaul updated successfully.',
                    'data' => $overhaul,
                ], 200);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update Overhaul.',
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update overhaul.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $overhaul = Overhaul::find($id);
        if (!$overhaul) {
            return response()->json([
                'success' => false,
                'message' => 'Overhaul not found.',
            ], 404);
        }
        try {
            if ($overhaul->laporan_file) {
                FileHelper::deleteFile($overhaul->laporan_file, 'laporan_inspection/overhaul');
            }
            $overhaul->delete();
            return response()->json([
                'success' => true,
                'message' => 'Overhaul deleted successfully.',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete Overhaul.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }
}
