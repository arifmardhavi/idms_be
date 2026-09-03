<?php

namespace App\Http\Controllers;

use App\Helpers\FileHelper;
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
            'nama_dokumen' => 'nullable|string|max:255',
            'no_dokumen' => 'nullable|string|max:255', // Validasi no_dokumen unik
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

            foreach ($request->file('drawing_file') as $file) {
                $filename = FileHelper::uploadWithVersion($file, 'engineering_data/ga_drawing');

                $drawing = GaDrawing::create([
                    'engineering_data_id' => $request->engineering_data_id,
                    'nama_dokumen' => $request->nama_dokumen,
                    'no_dokumen' => $request->no_dokumen,
                    'date_drawing' => $request->date_drawing,
                    'drawing_file' => $filename,
                ]);

                $result[] = $drawing;
            }

            return response()->json([
                'success' => true,
                'message' => 'Upload selesai.',
                'data' => $result,
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
            'nama_dokumen' => 'nullable|string|max:255',
            'no_dokumen' => 'nullable|string|max:255',
            'engineering_data_id' => 'required|exists:engineering_data,id',
            'drawing_file' => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png',
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
                $filename = FileHelper::uploadWithVersion($request->file('drawing_file'), 'engineering_data/ga_drawing');
                if ($ga_drawing->drawing_file) {
                    FileHelper::deleteFile($ga_drawing->drawing_file, 'engineering_data/ga_drawing');
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
                FileHelper::deleteFile($ga_drawing->drawing_file, 'engineering_data/ga_drawing');
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
