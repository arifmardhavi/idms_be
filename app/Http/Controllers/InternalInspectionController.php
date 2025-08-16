<?php

namespace App\Http\Controllers;

use App\Models\InternalInspection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class InternalInspectionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $internalInspection = InternalInspection::with('laporan_inspection', 'historical_memorandum')->get();
        return response()->json([
            'success' => true,
            'message' => 'Internal Inspection retrieved successfully.',
            'data' => $internalInspection,
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
            'laporan_file' => 'nullable|array',
            'laporan_file.*' => 'file|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png,zip,rar|max:204800',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed for Internal Inspection',
                'errors' => $validator->errors(),
            ], 422);
        }

        if ($request->file('laporan_file') && count($request->file('laporan_file')) > 10) {
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
                        while (file_exists(public_path("laporan_inspection/internal_inspection/" . $filename))) {
                            $version++;
                            $filename = $nameOnly . '_' . $dateNow . '_' . $version . '.' . $extension;
                        }
    
                        $path = $file->move(public_path('laporan_inspection/internal_inspection'), $filename);
                        if (!$path) {
                            $failedFiles[] = [
                                'name' => $originalName,
                                'error' => 'Gagal memindahkan file ke direktori tujuan.'
                            ];
                            continue;
                        }
    
                        $internalInspection = InternalInspection::create([
                            'laporan_inspection_id' => $request->laporan_inspection_id,
                            'judul' => $request->judul,
                            'inspection_date' => $request->inspection_date,
                            'historical_memorandum_id' => $request->historical_memorandum_id,
                            'laporan_file' => $filename,
                        ]);
    
                        $result[] = $internalInspection;
    
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
                $internalInspection = InternalInspection::create($validatedData);
                return response()->json([
                    'success' => true,
                    'message' => 'Internal Inspection created successfully.',
                    'data' => $internalInspection,
                ], 201);
            }

            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create Internal Inspection.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $internalInspection = InternalInspection::with('laporan_inspection', 'historical_memorandum')->find($id);
        if (!$internalInspection) {
            return response()->json([
                'success' => false,
                'message' => 'Internal Inspection not found.',
            ], 404);
        }
        return response()->json([
            'success' => true,
            'message' => 'Internal Inspection retrieved successfully.',
            'data' => $internalInspection,
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $internalInspection = InternalInspection::find($id);
        if (!$internalInspection) {
            return response()->json([
                'success' => false,
                'message' => 'Internal Inspection not found.',
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
                'message' => 'Validation failed for Internal Inspection',
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
                while (file_exists(public_path("laporan_inspection/internal_inspection/" . $filename))) {
                    $version++;
                    $filename = $originalName . '_' . $dateNow . '_' . $version . '.' . $extension;
                }
                $path = $file->move(public_path('laporan_inspection/internal_inspection'), $filename);
                if (!$path) {
                    return response()->json([
                        'success' => false,
                        'message' => 'File failed upload.',
                    ], 422);
                }
                if($internalInspection->laporan_file){
                    $internalInspectionBefore = public_path('laporan_inspection/internal_inspection/' . $internalInspection->laporan_file);
                    if (file_exists($internalInspectionBefore)) {
                        unlink($internalInspectionBefore); // Hapus file
                    }
                }
                $validatedData['laporan_file'] = $filename;
            }

            if ($internalInspection->update($validatedData)) {
                return response()->json([
                    'success' => true,
                    'message' => 'Internal Inspection updated successfully.',
                    'data' => $internalInspection,
                ], 200);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update Internal Inspection.',
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update Internal Inspection.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $internalInspection = InternalInspection::find($id);
        if (!$internalInspection) {
            return response()->json([
                'success' => false,
                'message' => 'Internal Inspection not found.',
            ], 404);
        }
        try {
            if ($internalInspection->laporan_file) {
                $filePath = public_path('laporan_inspection/internal_inspection/' . $internalInspection->laporan_file);
                if (file_exists($filePath)) {
                    unlink($filePath); // Hapus file
                }
            }
            $internalInspection->delete();
            return response()->json([
                'success' => true,
                'message' => 'Internal Inspection deleted successfully.',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete Internal Inspection.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }
}
