<?php

namespace App\Http\Controllers;

use App\Helpers\FileHelper;
use App\Models\Pir;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PirController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $pir =  Pir::orderBy('tanggal_pir', 'desc')->get();
        return response()->json([
            'success' => true,
            'message' => 'PIR retrieved successfully.',
            'data' => $pir,
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'judul' => 'required|string|max:255',
            'tanggal_pir' => 'required|date',
            'historical_memorandum_id' => 'nullable|exists:historical_memorandum,id',
            'pir_file' => 'nullable|file|mimes:pdf',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed for PIR',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validatedData = $validator->validated();

        try {
            if ($request->hasFile('pir_file')) {
                $validatedData['pir_file'] = FileHelper::uploadWithVersion($request->file('pir_file'), 'pir');  
            }

            $pir = Pir::create($validatedData);

            return response()->json([
                'success' => true,
                'message' => 'PIR created successfully.',
                'data' => $pir,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create PIR.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $pir = Pir::find($id);
        if (!$pir) {
            return response()->json([
                'success' => false,
                'message' => 'PIR not found.',
            ], 404);
        }
        return response()->json([
            'success' => true,
            'message' => 'PIR retrieved successfully.',
            'data' => $pir,
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $pir = Pir::find($id);
        if (!$pir) {
            return response()->json([
                'success' => false,
                'message' => 'PIR not found.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'judul' => 'sometimes|required|string|max:255',
            'tanggal_pir' => 'sometimes|required|date',
            'historical_memorandum_id' => 'nullable|exists:historical_memorandum,id',
            'pir_file' => 'sometimes|nullable|file|mimes:pdf',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed for PIR',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validatedData = $validator->validated();

        try {
            // Jika historical_memorandum_id diisi, hapus file lama
            if ($request->filled('historical_memorandum_id')) {
                if ($pir->pir_file) {
                    FileHelper::deleteFile($pir->pir_file, 'pir');
                    $validatedData['pir_file'] = null;
                }
            }

            // Jika ada file baru yang diupload, ganti file lama
            if ($request->hasFile('pir_file')) {
                $validatedData['pir_file'] = FileHelper::uploadWithVersion($request->file('pir_file'), 'pir');  
                // Delete old file if exists
                if ($pir->pir_file) {
                    FileHelper::deleteFile($pir->pir_file, 'pir');
                }
                $validatedData['historical_memorandum_id'] = null; // Set null karena pakai file baru              
            }

            $pir->update($validatedData);

            return response()->json([
                'success' => true,
                'message' => 'PIR updated successfully.',
                'data' => $pir,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update PIR.',
                'errors' => $e->getMessage(),
            ], 500);
        }


    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $pir = Pir::find($id);
        if (!$pir) {
            return response()->json([
                'success' => false,
                'message' => 'PIR not found.',
            ], 404);
        }

        try {
            if ($pir->pir_file) {
                $filePath = public_path('pir/' . $pir->pir_file);
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
            }
            $pir->delete();
            return response()->json([
                'success' => true,
                'message' => 'PIR deleted successfully.',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete PIR.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }
}
