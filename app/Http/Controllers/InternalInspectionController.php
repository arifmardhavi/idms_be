<?php

namespace App\Http\Controllers;

use App\Helpers\FileHelper;
use App\Models\InternalInspection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class InternalInspectionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $internalInspection = InternalInspection::with('laporan_inspection', 'historical_memorandum')->get();
        return response()->json([
            'success' => true,
            'message' => 'Internal Inspection retrieved successfully.',
            'data' => $internalInspection,
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
            'inspection_date' => 'required|date',
            'historical_memorandum_id' => 'nullable|exists:historical_memorandum,id',
            'laporan_file' => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png,zip,rar|max:204800',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed for Internal Inspection',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validatedData = $validator->validated();
        try {
            if ($request->hasFile('laporan_file')) {
                $validatedData['laporan_file'] = FileHelper::uploadWithVersion($request->file('laporan_file'), 'laporan_inspection/internal_inspection');
            }

            $internalInspection = InternalInspection::create($validatedData);

            return response()->json([
                'success' => true,
                'message' => 'Internal Inspection created successfully.',
                'data' => $internalInspection,
            ], 201);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create Internal Inspection.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $internalInspection = InternalInspection::with('laporan_inspection', 'historical_memorandum')->find($id);
        if (!$internalInspection) {
            return response()->json([
                'success' => false,
                'message' => 'Internal Inspection not found.',
            ], 404);
        }
        return response()->json([
            'success' => true,
            'message' => 'Internal Inspection retrieved successfully.',
            'data' => $internalInspection,
        ], 200);
    }
    public function showByLaporanInspection(string $id)
    {
        $internalInspection = InternalInspection::with('laporan_inspection', 'historical_memorandum')->where('laporan_inspection_id', $id)->get();
        if (!$internalInspection) {
            return response()->json([
                'success' => false,
                'message' => 'Internal Inspection not found.',
            ], 404);
        }
        return response()->json([
            'success' => true,
            'message' => 'Internal Inspection retrieved successfully.',
            'data' => $internalInspection,
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $internalInspection = InternalInspection::find($id);
        if (!$internalInspection) {
            return response()->json([
                'success' => false,
                'message' => 'Internal Inspection not found.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'judul' => 'required|string|max:255',
            'inspection_date' => 'required|date',
            'historical_memorandum_id' => 'nullable|exists:historical_memorandum,id',
            'laporan_file' => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png,zip,rar|max:204800',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed for Internal Inspection',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validatedData = $validator->validated();

        try {
            // Jika historical_memorandum_id diisi, hapus file lama
            if ($request->filled('historical_memorandum_id')) {
                if ($internalInspection->laporan_file) {
                    FileHelper::deleteFile($internalInspection->laporan_file, 'laporan_inspection/internal_inspection');
                }
                $validatedData['laporan_file'] = null; // Set null karena pakai memorandum
            }

            // Jika ada file baru diupload
            if ($request->hasFile('laporan_file')) {
                if ($internalInspection->laporan_file) {
                    FileHelper::deleteFile($internalInspection->laporan_file, 'laporan_inspection/internal_inspection');
                }

                // Jika ada file, maka hapus relasi historical memorandum
                if ($internalInspection->historical_memorandum_id) {
                    $validatedData['historical_memorandum_id'] = null;
                }

                $validatedData['laporan_file'] = FileHelper::uploadWithVersion($request->file('laporan_file'), 'laporan_inspection/internal_inspection');
            }

            if ($internalInspection->update($validatedData)) {
                return response()->json([
                    'success' => true,
                    'message' => 'Internal Inspection updated successfully.',
                    'data' => $internalInspection,
                ], 200);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update Internal Inspection.',
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update Internal Inspection.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }



    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $internalInspection = InternalInspection::find($id);
        if (!$internalInspection) {
            return response()->json([
                'success' => false,
                'message' => 'Internal Inspection not found.',
            ], 404);
        }
        try {
            if ($internalInspection->laporan_file) {
                FileHelper::deleteFile($internalInspection->laporan_file, 'laporan_inspection/internal_inspection');
            }
            $internalInspection->delete();
            return response()->json([
                'success' => true,
                'message' => 'Internal Inspection deleted successfully.',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete Internal Inspection.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }
}
