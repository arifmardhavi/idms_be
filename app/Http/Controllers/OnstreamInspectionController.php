<?php

namespace App\Http\Controllers;

use App\Models\OnstreamInspection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class OnstreamInspectionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $onstreamInspection = OnstreamInspection::with('laporan_inspection', 'historical_memorandum')->get();
        return response()->json([
            'success' => true,
            'message' => 'onstream Inspection retrieved successfully.',
            'data' => $onstreamInspection,
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
                'message' => 'Validation failed for Onstream Inspection',
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
                while (file_exists(public_path("laporan_inspection/onstream_inspection/".$filename))) {
                    $version++;
                    $filename = $originalName . '_' . $dateNow . '_' . $version . '.' . $extension;
                }
                // Store file in public/laporan_inspection/onstream_inspection/
                $path = $file->move(public_path('laporan_inspection/onstream_inspection'), $filename);  
                $validatedData['laporan_file'] = $filename;
            }

            $onstreamInspection = OnstreamInspection::create($validatedData);

            return response()->json([
                'success' => true,
                'message' => 'Onstream Inspection created successfully.',
                'data' => $onstreamInspection,
            ], 201);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create Onstream Inspection.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $onstreamInspection = OnstreamInspection::with('laporan_inspection', 'historical_memorandum')->find($id);
        if (!$onstreamInspection) {
            return response()->json([
                'success' => false,
                'message' => 'onstream Inspection not found.',
            ], 404);
        }
        return response()->json([
            'success' => true,
            'message' => 'onstream Inspection retrieved successfully.',
            'data' => $onstreamInspection,
        ], 200);
    }
    public function showByLaporanInspection(string $id)
    {
        $onstreamInspection = OnstreamInspection::with('laporan_inspection', 'historical_memorandum')->where('laporan_inspection_id', $id)->get();
        if (!$onstreamInspection) {
            return response()->json([
                'success' => false,
                'message' => 'onstream Inspection not found.',
            ], 404);
        }
        return response()->json([
            'success' => true,
            'message' => 'onstream Inspection retrieved successfully.',
            'data' => $onstreamInspection,
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $onstreamInspection = OnstreamInspection::find($id);
        if (!$onstreamInspection) {
            return response()->json([
                'success' => false,
                'message' => 'Onstream Inspection not found.',
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
                'message' => 'Validation failed for Onstream Inspection',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validatedData = $validator->validated();

        try {
            // Jika historical_memorandum_id diisi, hapus file lama
            if ($request->filled('historical_memorandum_id')) {
                if ($onstreamInspection->laporan_file) {
                    $onstreamInspectionBefore = public_path('laporan_inspection/onstream_inspection/' . $onstreamInspection->laporan_file);
                    if (file_exists($onstreamInspectionBefore)) {
                        unlink($onstreamInspectionBefore);
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
                while (file_exists(public_path("laporan_inspection/onstream_inspection/" . $filename))) {
                    $version++;
                    $filename = $originalName . '_' . $dateNow . '_' . $version . '.' . $extension;
                }

                $path = $file->move(public_path('laporan_inspection/onstream_inspection'), $filename);
                if (!$path) {
                    return response()->json([
                        'success' => false,
                        'message' => 'File failed upload.',
                    ], 422);
                }

                // hapus file lama jika ada
                if ($onstreamInspection->laporan_file) {
                    $onstreamInspectionBefore = public_path('laporan_inspection/onstream_inspection/' . $onstreamInspection->laporan_file);
                    if (file_exists($onstreamInspectionBefore)) {
                        unlink($onstreamInspectionBefore);
                    }
                }

                // Jika ada file, maka hapus relasi historical memorandum
                if ($onstreamInspection->historical_memorandum_id) {
                    $validatedData['historical_memorandum_id'] = null;
                }

                $validatedData['laporan_file'] = $filename;
            }

            if ($onstreamInspection->update($validatedData)) {
                return response()->json([
                    'success' => true,
                    'message' => 'Onstream Inspection updated successfully.',
                    'data' => $onstreamInspection,
                ], 200);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update Onstream Inspection.',
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update Onstream Inspection.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $onstreamInspection = OnstreamInspection::find($id);
        if (!$onstreamInspection) {
            return response()->json([
                'success' => false,
                'message' => 'onstream Inspection not found.',
            ], 404);
        }
        try {
            if ($onstreamInspection->laporan_file) {
                $filePath = public_path('laporan_inspection/onstream_inspection/' . $onstreamInspection->laporan_file);
                if (file_exists($filePath)) {
                    unlink($filePath); // Hapus file
                }
            }
            $onstreamInspection->delete();
            return response()->json([
                'success' => true,
                'message' => 'onstream Inspection deleted successfully.',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete onstream Inspection.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }
}
