<?php

namespace App\Http\Controllers;

use App\Helpers\FileHelper;
use App\Models\Surveillance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SurveillanceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $surveillance = Surveillance::with('laporan_inspection', 'historical_memorandum')->get();
        return response()->json([
            'success' => true,
            'message' => 'Surveillance retrieved successfully.',
            'data' => $surveillance,
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
            'surveillance_date' => 'required|date',
            'historical_memorandum_id' => 'nullable|exists:historical_memorandum,id',
            'laporan_file' => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png,zip,rar|max:204800',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed for Surveillance',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validatedData = $validator->validated();
        try {
            if ($request->hasFile('laporan_file')) {
                $validatedData['laporan_file'] = FileHelper::uploadWithVersion($request->file('laporan_file'), 'laporan_inspection/surveillance');
            }

            $surveillance = Surveillance::create($validatedData);

            return response()->json([
                'success' => true,
                'message' => 'Surveillance created successfully.',
                'data' => $surveillance,
            ], 201);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create Surveillance.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $surveillance = Surveillance::with('laporan_inspection', 'historical_memorandum')->find($id);
        if (!$surveillance) {
            return response()->json([
                'success' => false,
                'message' => 'Surveillance not found.',
            ], 404);
        }
        return response()->json([
            'success' => true,
            'message' => 'Surveillance retrieved successfully.',
            'data' => $surveillance,
        ], 200);
    }
    public function showByLaporanInspection(string $id)
    {
        $surveillance = Surveillance::with('laporan_inspection', 'historical_memorandum')->where('laporan_inspection_id', $id)->get();
        if (!$surveillance) {
            return response()->json([
                'success' => false,
                'message' => 'Surveillance not found.',
            ], 404);
        }
        return response()->json([
            'success' => true,
            'message' => 'Surveillance retrieved successfully.',
            'data' => $surveillance,
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $surveillance = Surveillance::find($id);
        if (!$surveillance) {
            return response()->json([
                'success' => false,
                'message' => 'Surveillance not found.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'judul' => 'required|string|max:255',
            'surveillance_date' => 'required|date',
            'historical_memorandum_id' => 'nullable|exists:historical_memorandum,id',
            'laporan_file' => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png,zip,rar|max:204800',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed for Surveillance',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validatedData = $validator->validated();

        try {
            // Jika historical_memorandum_id diisi, hapus file lama
            if ($request->filled('historical_memorandum_id')) {
                if ($surveillance->laporan_file) {
                    FileHelper::deleteFile($surveillance->laporan_file, 'laporan_inspection/surveillance');
                }
                $validatedData['laporan_file'] = null; // Set null karena pakai memorandum
            }

            // Jika ada file baru diupload
            if ($request->hasFile('laporan_file')) {
                if ($surveillance->laporan_file) {
                    FileHelper::deleteFile($surveillance->laporan_file, 'laporan_inspection/surveillance');
                }

                // Jika ada file, maka hapus relasi historical memorandum
                if ($surveillance->historical_memorandum_id) {
                    $validatedData['historical_memorandum_id'] = null;
                }

                $validatedData['laporan_file'] = FileHelper::uploadWithVersion($request->file('laporan_file'), 'laporan_inspection/surveillance');
            }

            if ($surveillance->update($validatedData)) {
                return response()->json([
                    'success' => true,
                    'message' => 'Surveillance updated successfully.',
                    'data' => $surveillance,
                ], 200);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update Surveillance.',
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update Surveillance.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $surveillance = Surveillance::find($id);
        if (!$surveillance) {
            return response()->json([
                'success' => false,
                'message' => 'Surveillance not found.',
            ], 404);
        }
        try {
            if ($surveillance->laporan_file) {
                FileHelper::deleteFile($surveillance->laporan_file, 'laporan_inspection/surveillance');
            }
            $surveillance->delete();
            return response()->json([
                'success' => true,
                'message' => 'Surveillance deleted successfully.',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete Surveillance.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }
}
