<?php

namespace App\Http\Controllers;

use App\Helpers\FileHelper;
use App\Models\ReportIzinDisnaker;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ReportIzinDisnakerController extends Controller
{
    public function index()
    {
        $reportIzinDisnaker = ReportIzinDisnaker::orderBy('id', 'desc')->get();

        return response()->json([
            'success' => true,
            'message' => 'Report Izin Disnaker retrieved successfully.',
            'data' => $reportIzinDisnaker,
        ], 200);
    }

    public function showWithIzinDisnakerId($id)
    {
        $reportIzinDisnaker = ReportIzinDisnaker::with(['izinDisnaker', 'izinDisnaker.plo.unit'])
            ->where('izin_disnaker_id', $id)
            ->get();

        if ($reportIzinDisnaker->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Report Izin Disnaker not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Report Izin Disnaker retrieved successfully.',
            'data' => $reportIzinDisnaker,
        ], 200);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'izin_disnaker_id' => 'required|exists:izin_disnakers,id',
            'report_izin_disnaker' => 'required|file|mimes:pdf',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi Report Izin Disnaker gagal',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validatedData = $validator->validated();

        try {
            if ($request->hasFile('report_izin_disnaker')) {
                $validatedData['report_izin_disnaker'] = FileHelper::uploadWithVersion($request->file('report_izin_disnaker'), 'izin_disnaker/reports');
            }

            $report = ReportIzinDisnaker::create($validatedData);

            if ($report) {
                return response()->json([
                    'success' => true,
                    'message' => 'Report Izin Disnaker created successfully.',
                    'data' => $report,
                ], 201);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to create Report Izin Disnaker.',
            ], 422);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create Report Izin Disnaker.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }

    public function show(string $id)
    {
        $report = ReportIzinDisnaker::with(['izinDisnaker'])->find($id);

        if (!$report) {
            return response()->json([
                'success' => false,
                'message' => 'Report Izin Disnaker not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Report Izin Disnaker retrieved successfully.',
            'data' => $report,
        ], 200);
    }

    public function update(Request $request, string $id)
    {
        $report = ReportIzinDisnaker::find($id);

        if (!$report) {
            return response()->json([
                'success' => false,
                'message' => 'Report Izin Disnaker not found.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'izin_disnaker_id' => 'required|exists:izin_disnakers,id',
            'report_izin_disnaker' => 'required|file|mimes:pdf',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi Report Izin Disnaker gagal',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validatedData = $validator->validated();

        try {
            if ($request->hasFile('report_izin_disnaker')) {
                if ($report->report_izin_disnaker) {
                    FileHelper::deleteFile($report->report_izin_disnaker, 'izin_disnaker/reports');
                }
                $validatedData['report_izin_disnaker'] = FileHelper::uploadWithVersion($request->file('report_izin_disnaker'), 'izin_disnaker/reports');
            }

            if ($report->update($validatedData)) {
                return response()->json([
                    'success' => true,
                    'message' => 'Report Izin Disnaker updated successfully.',
                    'data' => $report,
                ], 201);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to update Report Izin Disnaker.',
            ], 422);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update Report Izin Disnaker.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(string $id)
    {
        $report = ReportIzinDisnaker::find($id);

        if (!$report) {
            return response()->json([
                'success' => false,
                'message' => 'Report Izin Disnaker not found.',
            ], 404);
        }

        try {
            if ($report->report_izin_disnaker) {
                FileHelper::deleteFile($report->report_izin_disnaker, 'izin_disnaker/reports');
            }

            if ($report->delete()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Report Izin Disnaker deleted successfully.',
                ], 200);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete Report Izin Disnaker.',
            ], 422);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete Report Izin Disnaker.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }

    public function downloadReportIzinDisnakerFile(string $id)
    {
        $report = ReportIzinDisnaker::find($id);

        if (!$report) {
            return response()->json([
                'success' => false,
                'message' => 'Report Izin Disnaker not found.',
            ], 404);
        }

        if (!$report->report_izin_disnaker) {
            return response()->json([
                'success' => false,
                'message' => 'Report Izin Disnaker file not found.',
            ], 404);
        }

        activity()->log('download', 'ReportIzinDisnaker', [
            'recordId'    => $report->id,
            'recordLabel' => $report->no_izin ?? $report->id,
            'metadata'    => ['file' => $report->report_izin_disnaker],
        ]);

        return FileHelper::downloadFile('izin_disnaker/reports', $report->report_izin_disnaker);
    }
}
