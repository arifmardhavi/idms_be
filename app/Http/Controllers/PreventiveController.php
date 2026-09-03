<?php

namespace App\Http\Controllers;

use App\Helpers\FileHelper;
use App\Models\Preventive;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PreventiveController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $preventive = Preventive::with('laporan_inspection', 'historical_memorandum')->get();
        return response()->json([
            'success' => true,
            'message' => 'Preventive retrieved successfully.',
            'data' => $preventive,
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
            'preventive_date' => 'required|date',
            'historical_memorandum_id' => 'nullable|exists:historical_memorandum,id',
            'laporan_file' => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png|max:204800',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed for Preventive',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validatedData = $validator->validated();
        try {
            if ($request->hasFile('laporan_file')) {
                $validatedData['laporan_file'] = FileHelper::uploadWithVersion($request->file('laporan_file'), 'laporan_inspection/preventive');
            }

            $preventive = Preventive::create($validatedData);

            return response()->json([
                'success' => true,
                'message' => 'Preventive created successfully.',
                'data' => $preventive,
            ], 201);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create Preventive.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $preventive = Preventive::with('laporan_inspection', 'historical_memorandum')->find($id);
        if (!$preventive) {
            return response()->json([
                'success' => false,
                'message' => 'Preventive not found.',
            ], 404);
        }
        return response()->json([
            'success' => true,
            'message' => 'Preventive retrieved successfully.',
            'data' => $preventive,
        ], 200);
    }
    public function showByLaporanInspection(string $id)
    {
        $preventive = Preventive::with('laporan_inspection', 'historical_memorandum')->where('laporan_inspection_id', $id)->get();
        if (!$preventive) {
            return response()->json([
                'success' => false,
                'message' => 'Preventive not found.',
            ], 404);
        }
        return response()->json([
            'success' => true,
            'message' => 'Preventive retrieved successfully.',
            'data' => $preventive,
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $preventive = Preventive::find($id);
        if (!$preventive) {
            return response()->json([
                'success' => false,
                'message' => 'Preventive not found.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'judul' => 'required|string|max:255',
            'preventive_date' => 'required|date',
            'historical_memorandum_id' => 'nullable|exists:historical_memorandum,id',
            'laporan_file' => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png,zip,rar|max:204800',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed for Preventive',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validatedData = $validator->validated();

        try {
            // Jika historical_memorandum_id diisi, hapus file lama
            if ($request->filled('historical_memorandum_id')) {
                if ($preventive->laporan_file) {
                    FileHelper::deleteFile($preventive->laporan_file, 'laporan_inspection/preventive');
                }
                $validatedData['laporan_file'] = null; // Set null karena pakai memorandum
            }

            // Jika ada file baru diupload
            if ($request->hasFile('laporan_file')) {
                if ($preventive->laporan_file) {
                    FileHelper::deleteFile($preventive->laporan_file, 'laporan_inspection/preventive');
                }

                // Jika ada file, maka hapus relasi historical memorandum
                if ($preventive->historical_memorandum_id) {
                    $validatedData['historical_memorandum_id'] = null;
                }

                $validatedData['laporan_file'] = FileHelper::uploadWithVersion($request->file('laporan_file'), 'laporan_inspection/preventive');
            }

            if ($preventive->update($validatedData)) {
                return response()->json([
                    'success' => true,
                    'message' => 'Preventive updated successfully.',
                    'data' => $preventive,
                ], 200);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update Preventive.',
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update Preventive.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $preventive = Preventive::find($id);
        if (!$preventive) {
            return response()->json([
                'success' => false,
                'message' => 'Preventive not found.',
            ], 404);
        }
        try {
            if ($preventive->laporan_file) {
                FileHelper::deleteFile($preventive->laporan_file, 'laporan_inspection/preventive');
            }
            $preventive->delete();
            return response()->json([
                'success' => true,
                'message' => 'Preventive deleted successfully.',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete Preventive.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }
}
