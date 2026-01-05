<?php

namespace App\Http\Controllers;

use App\Models\Preventive;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PreventiveController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $preventive = Preventive::with('laporan_inspection', 'historical_memorandum')->get();
        return response()->json([
            'success' => true,
            'message' => 'Preventive retrieved successfully.',
            'data' => $preventive,
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
            'preventive_date' => 'required|date',
            'historical_memorandum_id' => 'nullable|exists:historical_memorandum,id',
            'laporan_file' => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png|max:204800',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed for Preventive',
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
                while (file_exists(public_path("laporan_inspection/preventive/".$filename))) {
                    $version++;
                    $filename = $originalName . '_' . $dateNow . '_' . $version . '.' . $extension;
                }
                // Store file in public/laporan_inspection/preventive/
                $path = $file->move(public_path('laporan_inspection/preventive'), $filename);  
                $validatedData['laporan_file'] = $filename;
            }

            $preventive = Preventive::create($validatedData);

            return response()->json([
                'success' => true,
                'message' => 'Preventive created successfully.',
                'data' => $preventive,
            ], 201);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create Preventive.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $preventive = Preventive::with('laporan_inspection', 'historical_memorandum')->find($id);
        if (!$preventive) {
            return response()->json([
                'success' => false,
                'message' => 'Preventive not found.',
            ], 404);
        }
        return response()->json([
            'success' => true,
            'message' => 'Preventive retrieved successfully.',
            'data' => $preventive,
        ], 200);
    }
    public function showByLaporanInspection(string $id)
    {
        $preventive = Preventive::with('laporan_inspection', 'historical_memorandum')->where('laporan_inspection_id', $id)->get();
        if (!$preventive) {
            return response()->json([
                'success' => false,
                'message' => 'Preventive not found.',
            ], 404);
        }
        return response()->json([
            'success' => true,
            'message' => 'Preventive retrieved successfully.',
            'data' => $preventive,
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $preventive = Preventive::find($id);
        if (!$preventive) {
            return response()->json([
                'success' => false,
                'message' => 'Preventive not found.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'judul' => 'required|string|max:255',
            'preventive_date' => 'required|date',
            'historical_memorandum_id' => 'nullable|exists:historical_memorandum,id',
            'laporan_file' => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png,zip,rar|max:204800',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed for Preventive',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validatedData = $validator->validated();

        try {
            // Jika historical_memorandum_id diisi, hapus file lama
            if ($request->filled('historical_memorandum_id')) {
                if ($preventive->laporan_file) {
                    $preventiveBefore = public_path('laporan_inspection/preventive/' . $preventive->laporan_file);
                    if (file_exists($preventiveBefore)) {
                        unlink($preventiveBefore);
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
                while (file_exists(public_path("laporan_inspection/preventive/" . $filename))) {
                    $version++;
                    $filename = $originalName . '_' . $dateNow . '_' . $version . '.' . $extension;
                }

                $path = $file->move(public_path('laporan_inspection/preventive'), $filename);
                if (!$path) {
                    return response()->json([
                        'success' => false,
                        'message' => 'File failed upload.',
                    ], 422);
                }

                // hapus file lama jika ada
                if ($preventive->laporan_file) {
                    $preventiveBefore = public_path('laporan_inspection/preventive/' . $preventive->laporan_file);
                    if (file_exists($preventiveBefore)) {
                        unlink($preventiveBefore);
                    }
                }

                // Jika ada file, maka hapus relasi historical memorandum
                if ($preventive->historical_memorandum_id) {
                    $validatedData['historical_memorandum_id'] = null;
                }

                $validatedData['laporan_file'] = $filename;
            }

            if ($preventive->update($validatedData)) {
                return response()->json([
                    'success' => true,
                    'message' => 'Preventive updated successfully.',
                    'data' => $preventive,
                ], 200);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update Preventive.',
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update Preventive.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $preventive = Preventive::find($id);
        if (!$preventive) {
            return response()->json([
                'success' => false,
                'message' => 'Preventive not found.',
            ], 404);
        }
        try {
            if ($preventive->laporan_file) {
                $filePath = public_path('laporan_inspection/preventive/' . $preventive->laporan_file);
                if (file_exists($filePath)) {
                    unlink($filePath); // Hapus file
                }
            }
            $preventive->delete();
            return response()->json([
                'success' => true,
                'message' => 'Preventive deleted successfully.',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete Preventive.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }
}
