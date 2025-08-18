<?php

namespace App\Http\Controllers;

use App\Models\ExternalInspection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ExternalInspectionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $externalInspection = ExternalInspection::with('laporan_inspection', 'historical_memorandum')->get();
        return response()->json([
            'success' => true,
            'message' => 'external Inspection retrieved successfully.',
            'data' => $externalInspection,
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
                'message' => 'Validation failed for External Inspection',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validatedData = $validator->validated();
        try {
            if ($request->hasFile('laporan_file')) {
                $file = $request->file('laporan_file');
                $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME); // Ambil nama file original tanpa ekstensi
                $extension = $file->getClientOriginalExtension(); // Ambil ekstensi file
                $dateNow = date('dmY'); // Tanggal sekarang dalam format ddmmyyyy
                $version = 0; // Awal versi
                // Format nama file
                $filename = $originalName . '_' . $dateNow . '_' . $version . '.' . $extension;

                // Cek apakah file dengan nama ini sudah ada di folder tujuan
                while (file_exists(public_path("laporan_inspection/external_inspection/".$filename))) {
                    $version++;
                    $filename = $originalName . '_' . $dateNow . '_' . $version . '.' . $extension;
                }
                // Store file in public/laporan_inspection/external_inspection/
                $path = $file->move(public_path('laporan_inspection/external_inspection'), $filename);  
                $validatedData['laporan_file'] = $filename;
            }

            $externalInspection = ExternalInspection::create($validatedData);

            return response()->json([
                'success' => true,
                'message' => 'External Inspection created successfully.',
                'data' => $externalInspection,
            ], 201);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create External Inspection.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $externalInspection = ExternalInspection::with('laporan_inspection', 'historical_memorandum')->find($id);
        if (!$externalInspection) {
            return response()->json([
                'success' => false,
                'message' => 'external Inspection not found.',
            ], 404);
        }
        return response()->json([
            'success' => true,
            'message' => 'external Inspection retrieved successfully.',
            'data' => $externalInspection,
        ], 200);
    }
    public function showByLaporanInspection(string $id)
    {
        $externalInspection = ExternalInspection::with('laporan_inspection', 'historical_memorandum')->where('laporan_inspection_id', $id)->get();
        if (!$externalInspection) {
            return response()->json([
                'success' => false,
                'message' => 'external Inspection not found.',
            ], 404);
        }
        return response()->json([
            'success' => true,
            'message' => 'external Inspection retrieved successfully.',
            'data' => $externalInspection,
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $externalInspection = ExternalInspection::find($id);
        if (!$externalInspection) {
            return response()->json([
                'success' => false,
                'message' => 'External Inspection not found.',
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
                'message' => 'Validation failed for External Inspection',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validatedData = $validator->validated();

        try {
            // Jika historical_memorandum_id diisi, hapus file lama
            if ($request->filled('historical_memorandum_id')) {
                if ($externalInspection->laporan_file) {
                    $externalInspectionBefore = public_path('laporan_inspection/external_inspection/' . $externalInspection->laporan_file);
                    if (file_exists($externalInspectionBefore)) {
                        unlink($externalInspectionBefore);
                    }
                }
                $validatedData['laporan_file'] = null; // Set null karena pakai memorandum
            }

            // Jika ada file baru diupload
            if ($request->hasFile('laporan_file')) {
                $file = $request->file('laporan_file');
                $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $extension = $file->getClientOriginalExtension();
                $dateNow = date('dmY');
                $version = 0;

                $filename = $originalName . '_' . $dateNow . '_' . $version . '.' . $extension;
                while (file_exists(public_path("laporan_inspection/external_inspection/" . $filename))) {
                    $version++;
                    $filename = $originalName . '_' . $dateNow . '_' . $version . '.' . $extension;
                }

                $path = $file->move(public_path('laporan_inspection/external_inspection'), $filename);
                if (!$path) {
                    return response()->json([
                        'success' => false,
                        'message' => 'File failed upload.',
                    ], 422);
                }

                // hapus file lama jika ada
                if ($externalInspection->laporan_file) {
                    $externalInspectionBefore = public_path('laporan_inspection/external_inspection/' . $externalInspection->laporan_file);
                    if (file_exists($externalInspectionBefore)) {
                        unlink($externalInspectionBefore);
                    }
                }

                // Jika ada file, maka hapus relasi historical memorandum
                if ($externalInspection->historical_memorandum_id) {
                    $validatedData['historical_memorandum_id'] = null;
                }

                $validatedData['laporan_file'] = $filename;
            }

            if ($externalInspection->update($validatedData)) {
                return response()->json([
                    'success' => true,
                    'message' => 'External Inspection updated successfully.',
                    'data' => $externalInspection,
                ], 200);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update External Inspection.',
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update External Inspection.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $externalInspection = ExternalInspection::find($id);
        if (!$externalInspection) {
            return response()->json([
                'success' => false,
                'message' => 'external Inspection not found.',
            ], 404);
        }
        try {
            if ($externalInspection->laporan_file) {
                $filePath = public_path('laporan_inspection/external_inspection/' . $externalInspection->laporan_file);
                if (file_exists($filePath)) {
                    unlink($filePath); // Hapus file
                }
            }
            $externalInspection->delete();
            return response()->json([
                'success' => true,
                'message' => 'external Inspection deleted successfully.',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete external Inspection.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }
}
