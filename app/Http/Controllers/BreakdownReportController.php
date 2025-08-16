<?php

namespace App\Http\Controllers;

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
            'laporan_file' => 'nullable|array',
            'laporan_file.*' => 'file|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png,zip,rar|max:204800',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed for Breakdown Report',
                'errors' => $validator->errors(),
            ], 422);
        }

        if (count($request->file('laporan_file')) > 10) {
            return response()->json([
                'success' => false,
                'message' => 'Maksimal upload 10 file.',
            ], 422);
        }

        try {
            if($request->file('laporan_file')){
                $result = [];
                $failedFiles = [];
                foreach ($request->file('laporan_file') as $file) {
                    $originalName = $file->getClientOriginalName();
    
                    try {
                        $nameOnly = pathinfo($originalName, PATHINFO_FILENAME);
                        $extension = $file->getClientOriginalExtension();
                        $dateNow = date('dmY');
                        $version = 0;
    
                        $filename = $nameOnly . '_' . $dateNow . '_' . $version . '.' . $extension;
                        while (file_exists(public_path("laporan_inspection/breakdown_report/" . $filename))) {
                            $version++;
                            $filename = $nameOnly . '_' . $dateNow . '_' . $version . '.' . $extension;
                        }
    
                        $path = $file->move(public_path('laporan_inspection/breakdown_report'), $filename);
                        if (!$path) {
                            $failedFiles[] = [
                                'name' => $originalName,
                                'error' => 'Gagal memindahkan file ke direktori tujuan.'
                            ];
                            continue;
                        }
    
                        $breakdownReport = BreakdownReport::create([
                            'laporan_inspection_id' => $request->laporan_inspection_id,
                            'judul' => $request->judul,
                            'breakdown_report_date' => $request->breakdown_report_date,
                            'historical_memorandum_id' => $request->historical_memorandum_id,
                            'laporan_file' => $filename,
                        ]);
    
                        $result[] = $breakdownReport;
    
                    } catch (\Throwable $fileError) {
                        $failedFiles[] = [
                            'name' => $originalName,
                            'error' => $fileError->getMessage()
                        ];
                    }
                }
                return response()->json([
                    'success' => true,
                    'message' => 'Upload selesai.',
                    'data' => $result,
                    'failed_files' => $failedFiles,
                ], 201);
            }else{
                $validatedData = $validator->validated();
                $breakdownReport = BreakdownReport::create($validatedData);
                return response()->json([
                    'success' => true,
                    'message' => 'Breakdown Report created successfully.',
                    'data' => $breakdownReport,
                ], 201);
            }
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create Breakdown Report.',
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
            if ($request->hasFile('laporan_file')) {
                $file = $request->file('laporan_file');
                $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $extension = $file->getClientOriginalExtension();
                $dateNow = date('dmY');
                $version = 0;
                $filename = $originalName . '_' . $dateNow . '_' . $version . '.' . $extension;
                while (file_exists(public_path("laporan_inspection/breakdown_report/" . $filename))) {
                    $version++;
                    $filename = $originalName . '_' . $dateNow . '_' . $version . '.' . $extension;
                }
                $path = $file->move(public_path('laporan_inspection/breakdown_report'), $filename);
                if (!$path) {
                    return response()->json([
                        'success' => false,
                        'message' => 'File failed upload.',
                    ], 422);
                }
                if($breakdownReport->laporan_file){
                    $breakdownReportBefore = public_path('laporan_inspection/breakdown_report/' . $breakdownReport->laporan_file);
                    if (file_exists($breakdownReportBefore)) {
                        unlink($breakdownReportBefore); // Hapus file
                    }
                }
                $validatedData['laporan_file'] = $filename;
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
                $filePath = public_path('laporan_inspection/breakdown_report/' . $breakdownReport->laporan_file);
                if (file_exists($filePath)) {
                    unlink($filePath); // Hapus file
                }
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
