<?php

namespace App\Http\Controllers;

use App\Models\GaDrawing;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class GaDrawingController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $ga_drawing = GaDrawing::orderBy('id', 'desc')->get();
        return response()->json([
            'success' => true,
            'message' => 'GA Drawing retrieved successfully.',
            'data' => $ga_drawing,
        ], 200);
    }

    public function showWithEngineeringDataId($id)
    {
        $ga_drawing = GaDrawing::with(['engineeringData', 'engineeringData.tagNumber'])
            ->where('engineering_data_id', $id)
            ->get();

        if ($ga_drawing->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'GA Drawing not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'GA Drawing retrieved successfully.',
            'data' => $ga_drawing,
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'no_dokumen' => 'nullable|string|max:255|unique:ga_drawings,no_dokumen', // Validasi no_dokumen unik
            'engineering_data_id' => 'required|exists:engineering_data,id',
            'date_drawing' => 'nullable|date', // Tambahkan validasi untuk tanggal drawing
            'drawing_file' => 'required|array',
            'drawing_file.*' => 'file|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png,zip,rar|max:204800',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed for GA Drawing',
                'errors' => $validator->errors(),
            ], 422);
        }
        if (count($request->file('drawing_file')) > 10) {
            return response()->json([
                'success' => false,
                'message' => 'Maksimal upload 10 file.',
            ], 422);
        }

        try {
            $result = [];
            $failedFiles = [];

            foreach ($request->file('drawing_file') as $file) {
                $originalName = $file->getClientOriginalName();

                try {
                    $nameOnly = pathinfo($originalName, PATHINFO_FILENAME);
                    $extension = $file->getClientOriginalExtension();
                    $dateNow = date('dmY');
                    $version = 0;

                    $filename = $nameOnly . '_' . $dateNow . '_' . $version . '.' . $extension;
                    while (file_exists(public_path("engineering_data/ga_drawing/" . $filename))) {
                        $version++;
                        $filename = $nameOnly . '_' . $dateNow . '_' . $version . '.' . $extension;
                    }

                    $path = $file->move(public_path('engineering_data/ga_drawing'), $filename);
                    if (!$path) {
                        $failedFiles[] = [
                            'name' => $originalName,
                            'error' => 'Gagal memindahkan file ke direktori tujuan.'
                        ];
                        continue;
                    }

                    $drawing = GaDrawing::create([
                        'engineering_data_id' => $request->engineering_data_id,
                        'no_dokumen' => $request->no_dokumen,
                        'date_drawing' => $request->date_drawing,
                        'drawing_file' => $filename,
                    ]);

                    $result[] = $drawing;

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

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal upload drawing.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $ga_drawing = GaDrawing::find($id);
        if (!$ga_drawing) {
            return response()->json([
                'success' => false,
                'message' => 'GA Drawing not found.',
            ], 404);
        }
        return response()->json([
            'success' => true,
            'message' => 'GA Drawing retrieved successfully.',
            'data' => $ga_drawing,
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $ga_drawing = GaDrawing::find($id);
        if (!$ga_drawing) {
            return response()->json([
                'success' => false,
                'message' => 'GA Drawing not found.',
            ], 404);
        }
        $validator = Validator::make($request->all(), [
            'no_dokumen' => 'nullable|string|max:255|unique:ga_drawings,no_dokumen,' . $id, // Validasi no_dokumen unik kecuali untuk ID yang sama
            'engineering_data_id' => 'required|exists:engineering_data,id',
            'drawing_file' => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png,zip,rar|max:20480',
            'date_drawing' => 'nullable|date', // Tambahkan validasi untuk tanggal drawing
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed for GA Drawing',
                'errors' => $validator->errors(),
            ], 422);
        }
        $validatedData = $validator->validated();
        try {
            if ($request->hasFile('drawing_file')) {
                $file = $request->file('drawing_file');
                $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $extension = $file->getClientOriginalExtension();
                $dateNow = date('dmY');
                $version = 0;
                $filename = $originalName . '_' . $dateNow . '_' . $version . '.' . $extension;
                while (file_exists(public_path("engineering_data/ga_drawing/" . $filename))) {
                    $version++;
                    $filename = $originalName . '_' . $dateNow . '_' . $version . '.' . $extension;
                }
                $path = $file->move(public_path('engineering_data/ga_drawing'), $filename);
                if (!$path) {
                    return response()->json([
                        'success' => false,
                        'message' => 'GA Drawing failed upload.',
                    ], 422);
                }
                $validatedData['drawing_file'] = $filename;
            }

            if ($ga_drawing->update($validatedData)) {
                return response()->json([
                    'success' => true,
                    'message' => 'GA Drawing updated successfully.',
                    'data' => $ga_drawing,
                ], 200);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update GA Drawing.',
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update GA Drawing file.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $ga_drawing = GaDrawing::find($id);

        if (!$ga_drawing) {
            return response()->json([
                'success' => false,
                'message' => 'GA Drawing not found.',
            ], 404);
        }

        try {
            if ($ga_drawing->drawing_file) {
                // Hapus file dari direktori
                $path = public_path('engineering_data/ga_drawing/' . $ga_drawing->drawing_file);
                if (file_exists($path)) {
                    unlink($path); // Hapus file
                }
            }
            if($ga_drawing->delete()){
                return response()->json([
                    'success' => true,
                    'message' => 'GA Drawing deleted successfully.',
                ], 200);
            }else{
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to delete GA Drawing.',
                ], 422);
            }
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete GA Drawing.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }
}
