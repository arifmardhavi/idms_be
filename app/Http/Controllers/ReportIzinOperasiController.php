<?php

namespace App\Http\Controllers;

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
        $reportIzinOperasi = ReportIzinOperasi::with(['izin_operasi', 'izin_operasi.plo', 'izin_operasi.plo.unit'])->where('izin_operasi_id', $id)->get();

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
                $file = $request->file('report_izin_operasi');
                $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME); // Ambil nama file original tanpa ekstensi
                $extension = $file->getClientOriginalExtension(); // Ambil ekstensi file
                $dateNow = date('dmY'); // Tanggal sekarang dalam format ddmmyyyy
                $version = 0; // Awal versi
                $filename = $originalName . '_' . $dateNow . '_' . $version . '.' . $extension; // Nama file baru dengan versi
                while (file_exists(public_path("izin_operasi/reports/".$filename))) {
                    $version++; // Increment versi
                    $filename = $originalName . '_' . $dateNow . '_' . $version . '.' . $extension; // Nama file baru dengan versi baru
                }
                $path = $file->move(public_path('izin_operasi/reports'), $filename);
                if(!$path){
                    return response()->json([
                        'success' => false,
                        'message' => 'Report Izin Operasi failed upload.',
                    ], 422);
                }  
                $validatedData['report_izin_operasi'] = $filename;
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
                $file = $request->file('report_izin_operasi');
                $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME); // Ambil nama file original tanpa ekstensi
                $extension = $file->getClientOriginalExtension(); // Ambil ekstensi file
                $dateNow = date('dmY'); // Tanggal sekarang dalam format ddmmyyyy
                $version = 0; // Awal versi
                $filename = $originalName . '_' . $dateNow . '_' . $version . '.' . $extension; // Nama file baru dengan versi
                while (file_exists(public_path("izin_operasi/reports/".$filename))) {
                    $version++; // Increment versi
                    $filename = $originalName . '_' . $dateNow . '_' . $version . '.' . $extension; // Nama file baru dengan versi baru
                }
                $path = $file->move(public_path('izin_operasi/reports'), $filename);
                if(!$path){
                    return response()->json([
                        'success' => false,
                        'message' => 'Report Izin Operasi failed upload.',
                    ], 422);
                }
                if($report->report_izin_operasi){
                    $reportBefore = public_path('izin_operasi/reports/' . $report->report_izin_operasi);
                    if (file_exists($reportBefore)) {
                        unlink($reportBefore); // Hapus file
                    }
                }

                $validatedData['report_izin_operasi'] = $filename;
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
                $path = public_path('izin_operasi/reports/' . $report->report_izin_operasi);
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
