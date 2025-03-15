<?php

namespace App\Http\Controllers;

use App\Models\ReportPlo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use SebastianBergmann\CodeCoverage\Report\Xml\Report;

class ReportPloController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $reportPlo = ReportPlo::orderBy('id', 'desc')->get();

        return response()->json([
            'success' => true,
            'message' => 'Report PLO retrieved successfully.',
            'data' => $reportPlo,
        ], 200);
    }

    public function showWithPloId($id)
    {
        $reportPlo = ReportPlo::with(['plo', 'plo.unit'])->where('plo_id', $id)->get();

        if ($reportPlo->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Report PLO not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Report PLO retrieved successfully.',
            'data' => $reportPlo,
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'plo_id' => 'required|exists:plos,id',
            'report_plo' => 'required|file|mimes:pdf',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi Report PLO gagal',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validatedData = $validator->validated();
        try {
            if ($request->hasFile('report_plo')) {
                $file = $request->file('report_plo');
                $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME); // Ambil nama file original tanpa ekstensi
                $extension = $file->getClientOriginalExtension(); // Ambil ekstensi file
                $dateNow = date('dmY'); // Tanggal sekarang dalam format ddmmyyyy
                $version = 0; // Awal versi
                $filename = $originalName . '_' . $dateNow . '_' . $version . '.' . $extension; // Nama file baru dengan versi
                while (file_exists(public_path("plo/reports/".$filename))) {
                    $version++; // Increment versi
                    $filename = $originalName . '_' . $dateNow . '_' . $version . '.' . $extension; // Nama file baru dengan versi baru
                }
                $path = $file->move(public_path('plo/reports'), $filename);
                if(!$path){
                    return response()->json([
                        'success' => false,
                        'message' => 'Report PLO failed upload.',
                    ], 422);
                }  
                $validatedData['report_plo'] = $filename;
            }
            $report = ReportPlo::create($validatedData);
            if($report){
                return response()->json([
                    'success' => true,
                    'message' => 'Report PLO created successfully.',
                    'data' => $report,
                ], 201);
            }else{
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create Report PLO.',
                ], 422);
            }
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create Report PLO.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $report = ReportPlo::with(['plo'])->find($id);

        if (!$report) {
            return response()->json([
                'success' => false,
                'message' => 'Report PLO not found.',
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
        $report = ReportPlo::find($id);
        if (!$report) {
            return response()->json([
                'success' => false,
                'message' => 'Report PLO not found.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'plo_id' => 'required|exists:plos,id',
            'report_plo' => 'required|file|mimes:pdf',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi Report PLO gagal',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validatedData = $validator->validated();
        try {
            if ($request->hasFile('report_plo')) {
                $file = $request->file('report_plo');
                $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME); // Ambil nama file original tanpa ekstensi
                $extension = $file->getClientOriginalExtension(); // Ambil ekstensi file
                $dateNow = date('dmY'); // Tanggal sekarang dalam format ddmmyyyy
                $version = 0; // Awal versi
                $filename = $originalName . '_' . $dateNow . '_' . $version . '.' . $extension; // Nama file baru dengan versi
                while (file_exists(public_path("plo/reports/".$filename))) {
                    $version++; // Increment versi
                    $filename = $originalName . '_' . $dateNow . '_' . $version . '.' . $extension; // Nama file baru dengan versi baru
                }
                $path = $file->move(public_path('plo/reports'), $filename);
                if(!$path){
                    return response()->json([
                        'success' => false,
                        'message' => 'Report PLO failed upload.',
                    ], 422);
                }
                if($report->report_plo){
                    $reportBefore = public_path('plo/reports/' . $report->report_plo);
                    if (file_exists($reportBefore)) {
                        unlink($reportBefore); // Hapus file
                    }
                }

                $validatedData['report_plo'] = $filename;
            }
            
            if($report->update($validatedData)){
                return response()->json([
                    'success' => true,
                    'message' => 'Report PLO updated successfully.',
                    'data' => $report,
                ], 201);
            }else{
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update Report PLO.',
                ], 422);
            }
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update Report PLO.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $report = ReportPlo::find($id);

        if (!$report) {
            return response()->json([
                'success' => false,
                'message' => 'Report PLO not found.',
            ], 404);
        }

        try {
            if ($report->report_plo) {
                $path = public_path('plo/reports/' . $report->report_plo);
                if (file_exists($path)) {
                    unlink($path); // Hapus file
                }
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
}
