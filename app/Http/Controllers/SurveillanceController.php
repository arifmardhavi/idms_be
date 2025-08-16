<?php

namespace App\Http\Controllers;

use App\Models\Surveillance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SurveillanceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $surveillance = Surveillance::with('laporan_inspection', 'historical_memorandum')->get();
        return response()->json([
            'success' => true,
            'message' => 'Surveillance retrieved successfully.',
            'data' => $surveillance,
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
            'surveillance_date' => 'required|date',
            'historical_memorandum_id' => 'nullable|exists:historical_memorandum,id',
            'laporan_file' => 'nullable|array',
            'laporan_file.*' => 'file|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png,zip,rar|max:204800',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed for Surveillance',
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
                        while (file_exists(public_path("laporan_inspection/surveillance/" . $filename))) {
                            $version++;
                            $filename = $nameOnly . '_' . $dateNow . '_' . $version . '.' . $extension;
                        }
    
                        $path = $file->move(public_path('laporan_inspection/surveillance'), $filename);
                        if (!$path) {
                            $failedFiles[] = [
                                'name' => $originalName,
                                'error' => 'Gagal memindahkan file ke direktori tujuan.'
                            ];
                            continue;
                        }
    
                        $surveillance = Surveillance::create([
                            'laporan_inspection_id' => $request->laporan_inspection_id,
                            'judul' => $request->judul,
                            'surveillance_date' => $request->surveillance_date,
                            'historical_memorandum_id' => $request->historical_memorandum_id,
                            'laporan_file' => $filename,
                        ]);
    
                        $result[] = $surveillance;
    
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
                $surveillance = Surveillance::create($validatedData);
                return response()->json([
                    'success' => true,
                    'message' => 'Surveillance created successfully.',
                    'data' => $surveillance,
                ], 201);
            }
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create Surveillance.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $surveillance = Surveillance::with('laporan_inspection', 'historical_memorandum')->find($id);
        if (!$surveillance) {
            return response()->json([
                'success' => false,
                'message' => 'Surveillance not found.',
            ], 404);
        }
        return response()->json([
            'success' => true,
            'message' => 'Surveillance retrieved successfully.',
            'data' => $surveillance,
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $surveillance = Surveillance::find($id);
        if (!$surveillance) {
            return response()->json([
                'success' => false,
                'message' => 'Surveillance not found.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'judul' => 'required|string|max:255',
            'surveillance_date' => 'required|date',
            'historical_memorandum_id' => 'nullable|exists:historical_memorandum,id',
            'laporan_file' => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png,zip,rar|max:204800',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed for Surveillance',
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
                while (file_exists(public_path("laporan_inspection/surveillance/" . $filename))) {
                    $version++;
                    $filename = $originalName . '_' . $dateNow . '_' . $version . '.' . $extension;
                }
                $path = $file->move(public_path('laporan_inspection/surveillance'), $filename);
                if (!$path) {
                    return response()->json([
                        'success' => false,
                        'message' => 'File failed upload.',
                    ], 422);
                }
                if($surveillance->laporan_file){
                    $surveillanceBefore = public_path('laporan_inspection/surveillance/' . $surveillance->laporan_file);
                    if (file_exists($surveillanceBefore)) {
                        unlink($surveillanceBefore); // Hapus file
                    }
                }
                $validatedData['laporan_file'] = $filename;
            }

            if ($surveillance->update($validatedData)) {
                return response()->json([
                    'success' => true,
                    'message' => 'Surveillance updated successfully.',
                    'data' => $surveillance,
                ], 200);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update Surveillance.',
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update Surveillance.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $surveillance = Surveillance::find($id);
        if (!$surveillance) {
            return response()->json([
                'success' => false,
                'message' => 'Surveillance not found.',
            ], 404);
        }
        try {
            if ($surveillance->laporan_file) {
                $filePath = public_path('laporan_inspection/surveillance/' . $surveillance->laporan_file);
                if (file_exists($filePath)) {
                    unlink($filePath); // Hapus file
                }
            }
            $surveillance->delete();
            return response()->json([
                'success' => true,
                'message' => 'Surveillance deleted successfully.',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete Surveillance.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }
}
