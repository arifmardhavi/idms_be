<?php

namespace App\Http\Controllers;

use App\Helpers\FileHelper;
use App\Models\ReportIzinOperasi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ReportIzinOperasiController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $reportIzinOperasi = ReportIzinOperasi::orderBy('id', 'desc')->get();

        return response()->json([
            'success' => true,
            'message' => 'Report Izin Operasi retrieved successfully.',
            'data' => $reportIzinOperasi,
        ], 200);
    }

    public function showWithIzinOperasiId($id)
    {
        $reportIzinOperasi = ReportIzinOperasi::with(['izin_operasi', 'izin_operasi.unit'])->where('izin_operasi_id', $id)->get();

        if ($reportIzinOperasi->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Report Izin Operasi not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Report Izin Operasi retrieved successfully.',
            'data' => $reportIzinOperasi,
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'izin_operasi_id' => 'required|exists:izin_operasis,id',
            'report_izin_operasi' => 'required|file|mimes:pdf',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi Report Izin Operasi gagal',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validatedData = $validator->validated();
        try {
            if ($request->hasFile('report_izin_operasi')) {
                $validatedData['report_izin_operasi'] = FileHelper::uploadWithVersion($request->file('report_izin_operasi'), 'izin_operasi/reports');
            }
            $report = ReportIzinOperasi::create($validatedData);
            if($report){
                return response()->json([
                    'success' => true,
                    'message' => 'Report Izin Operasi created successfully.',
                    'data' => $report,
                ], 201);
            }else{
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create Report Izin Operasi.',
                ], 422);
            }
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create Report Izin Operasi.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $report = ReportIzinOperasi::with(['izin_operasi'])->find($id);

        if (!$report) {
            return response()->json([
                'success' => false,
                'message' => 'Report Izin Operasi not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'report retrieved successfully.',
            'data' => $report,
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $report = ReportIzinOperasi::find($id);
        if (!$report) {
            return response()->json([
                'success' => false,
                'message' => 'Report Izin Operasi not found.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'izin_operasi_id' => 'required|exists:izin_operasis,id',
            'report_izin_operasi' => 'required|file|mimes:pdf',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi Report Izin Operasi gagal',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validatedData = $validator->validated();
        try {
            if ($request->hasFile('report_izin_operasi')) {
                if ($report->report_izin_operasi) {
                    FileHelper::deleteFile($report->report_izin_operasi, 'izin_operasi/reports');
                }
                $validatedData['report_izin_operasi'] = FileHelper::uploadWithVersion($request->file('report_izin_operasi'), 'izin_operasi/reports');
            }
            
            if($report->update($validatedData)){
                return response()->json([
                    'success' => true,
                    'message' => 'Report Izin Operasi updated successfully.',
                    'data' => $report,
                ], 201);
            }else{
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update Report Izin Operasi.',
                ], 422);
            }
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update Report Izin Operasi.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $report = ReportIzinOperasi::find($id);

        if (!$report) {
            return response()->json([
                'success' => false,
                'message' => 'Report Izin Operasi not found.',
            ], 404);
        }

        try {
            if ($report->report_izin_operasi) {
                FileHelper::deleteFile($report->report_izin_operasi, 'izin_operasi/reports');
            }
            if($report->delete()){
                return response()->json([
                    'success' => true,
                    'message' => 'report deleted successfully.',
                ], 200);
            }else{
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to delete report.',
                ], 422);
            }
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete report.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }

    public function downloadReportIzinOperasiFile(string $id)
    {
        $report = ReportIzinOperasi::find($id);

        if (!$report) {
            return response()->json([
                'success' => false,
                'message' => 'Report Izin Operasi not found.',
            ], 404);
        }

        if (!$report->report_izin_operasi) {
            return response()->json([
                'success' => false,
                'message' => 'Report Izin Operasi file not found.',
            ], 404);
        }

        activity()->log('download', 'ReportIzinOperasi', [
            'recordId'    => $report->id,
            'recordLabel' => $report->no_izin ?? $report->id,
            'metadata'    => ['file' => $report->report_izin_operasi],
        ]);

        return FileHelper::downloadFile('izin_operasi/reports', $report->report_izin_operasi);
    }
}
