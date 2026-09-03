<?php

namespace App\Http\Controllers;

use App\Helpers\FileHelper;
use App\Models\BreakdownReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BreakdownReportController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $breakdownReport = BreakdownReport::with('laporan_inspection', 'historical_memorandum')->get();
        return response()->json([
            'success' => true,
            'message' => 'Breakdown Report retrieved successfully.',
            'data' => $breakdownReport,
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
            'breakdown_report_date' => 'required|date',
            'historical_memorandum_id' => 'nullable|exists:historical_memorandum,id',
            'laporan_file' => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png,zip,rar|max:204800',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed for  Breakdown Report',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validatedData = $validator->validated();
        try {
            if ($request->hasFile('laporan_file')) {
                $validatedData['laporan_file'] = FileHelper::uploadWithVersion($request->file('laporan_file'), 'laporan_inspection/breakdown_report');
            }

            $breakdownReport = BreakdownReport::create($validatedData);

            return response()->json([
                'success' => true,
                'message' => ' Breakdown Report created successfully.',
                'data' => $breakdownReport,
            ], 201);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create  Breakdown Report.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $breakdownReport = BreakdownReport::with('laporan_inspection', 'historical_memorandum')->find($id);
        if (!$breakdownReport) {
            return response()->json([
                'success' => false,
                'message' => 'Breakdown Report not found.',
            ], 404);
        }
        return response()->json([
            'success' => true,
            'message' => 'Breakdown Report retrieved successfully.',
            'data' => $breakdownReport,
        ], 200);
    }
    public function showByLaporanInspection(string $id)
    {
        $breakdownReport = BreakdownReport::with('laporan_inspection', 'historical_memorandum')->where('laporan_inspection_id', $id)->get();
        if (!$breakdownReport) {
            return response()->json([
                'success' => false,
                'message' => 'Breakdown Report not found.',
            ], 404);
        }
        return response()->json([
            'success' => true,
            'message' => 'Breakdown Report retrieved successfully.',
            'data' => $breakdownReport,
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $breakdownReport = BreakdownReport::find($id);
        if (!$breakdownReport) {
            return response()->json([
                'success' => false,
                'message' => 'Breakdown Report not found.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'judul' => 'required|string|max:255',
            'breakdown_report_date' => 'required|date',
            'historical_memorandum_id' => 'nullable|exists:historical_memorandum,id',
            'laporan_file' => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png,zip,rar|max:204800',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed for Breakdown Report',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validatedData = $validator->validated();

        try {
            // Jika historical_memorandum_id diisi, hapus file lama
            if ($request->filled('historical_memorandum_id')) {
                if ($breakdownReport->laporan_file) {
                    FileHelper::deleteFile($breakdownReport->laporan_file, 'laporan_inspection/breakdown_report');
                }
                $validatedData['laporan_file'] = null; // Set null karena pakai memorandum
            }

            // Jika ada file baru diupload
            if ($request->hasFile('laporan_file')) {
                if ($breakdownReport->laporan_file) {
                    FileHelper::deleteFile($breakdownReport->laporan_file, 'laporan_inspection/breakdown_report');
                }

                // Jika ada file, maka hapus relasi historical memorandum
                if ($breakdownReport->historical_memorandum_id) {
                    $validatedData['historical_memorandum_id'] = null;
                }

                $validatedData['laporan_file'] = FileHelper::uploadWithVersion($request->file('laporan_file'), 'laporan_inspection/breakdown_report');
            }

            if ($breakdownReport->update($validatedData)) {
                return response()->json([
                    'success' => true,
                    'message' => 'Breakdown Report updated successfully.',
                    'data' => $breakdownReport,
                ], 200);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update Breakdown Report.',
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update Breakdown Report.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $breakdownReport = BreakdownReport::find($id);
        if (!$breakdownReport) {
            return response()->json([
                'success' => false,
                'message' => 'Breakdown Report not found.',
            ], 404);
        }
        try {
            if ($breakdownReport->laporan_file) {
                FileHelper::deleteFile($breakdownReport->laporan_file, 'laporan_inspection/breakdown_report');
            }
            $breakdownReport->delete();
            return response()->json([
                'success' => true,
                'message' => 'Breakdown Report deleted successfully.',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete Breakdown Report.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }
}
