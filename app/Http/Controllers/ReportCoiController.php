<?php

namespace App\Http\Controllers;

use App\Helpers\FileHelper;
use App\Models\ReportCoi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ReportCoiController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $reportCoi = ReportCoi::orderBy('id', 'desc')->get();

        return response()->json([
            'success' => true,
            'message' => 'Report COI retrieved successfully.',
            'data' => $reportCoi,
        ], 200);
    }

    public function showWithCoiId($id)
    {
        $reportCoi = ReportCoi::with(['coi', 'coi.plo', 'coi.plo.unit'])->where('coi_id', $id)->get();

        if ($reportCoi->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Report COI not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Report COI retrieved successfully.',
            'data' => $reportCoi,
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'coi_id' => 'required|exists:cois,id',
            'report_coi' => 'required|file|mimes:pdf',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi Report COI gagal',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validatedData = $validator->validated();
        try {
            if ($request->hasFile('report_coi')) {
                $validatedData['report_coi'] = FileHelper::uploadWithVersion($request->file('report_coi'), 'coi/reports');
            }
            $report = ReportCoi::create($validatedData);
            if($report){
                return response()->json([
                    'success' => true,
                    'message' => 'Report COI created successfully.',
                    'data' => $report,
                ], 201);
            }else{
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create Report COI.',
                ], 422);
            }
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create Report COI.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $report = ReportCoi::with(['coi'])->find($id);

        if (!$report) {
            return response()->json([
                'success' => false,
                'message' => 'Report COI not found.',
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
        $report = ReportCoi::find($id);
        if (!$report) {
            return response()->json([
                'success' => false,
                'message' => 'Report COI not found.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'coi_id' => 'required|exists:cois,id',
            'report_coi' => 'required|file|mimes:pdf',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi Report COI gagal',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validatedData = $validator->validated();
        try {
            if ($request->hasFile('report_coi')) {
                if ($report->report_coi) {
                    FileHelper::deleteFile($report->report_coi, 'coi/reports');
                }
                $validatedData['report_coi'] = FileHelper::uploadWithVersion($request->file('report_coi'), 'coi/reports');
            }
            
            if($report->update($validatedData)){
                return response()->json([
                    'success' => true,
                    'message' => 'Report COI updated successfully.',
                    'data' => $report,
                ], 201);
            }else{
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update Report COI.',
                ], 422);
            }
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update Report COI.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $report = ReportCoi::find($id);

        if (!$report) {
            return response()->json([
                'success' => false,
                'message' => 'Report COI not found.',
            ], 404);
        }

        try {
            if ($report->report_coi) {
                FileHelper::deleteFile($report->report_coi, 'coi/reports');
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

    public function downloadReportCoiFile(string $id)
    {
        $report = ReportCoi::find($id);

        if (!$report) {
            return response()->json([
                'success' => false,
                'message' => 'Report COI not found.',
            ], 404);
        }

        if (!$report->report_coi) {
            return response()->json([
                'success' => false,
                'message' => 'Report COI file not found.',
            ], 404);
        }

        activity()->log('download', 'ReportCoi', [
            'recordId'    => $report->id,
            'recordLabel' => $report->no_certificate ?? $report->id,
            'metadata'    => ['file' => $report->report_coi],
        ]);

        return FileHelper::downloadFile('coi/reports', $report->report_coi);
    }
}
