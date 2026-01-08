<?php

namespace App\Http\Controllers;

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
            'pir_file' => 'required|file|mimes:pdf',
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
                $file = $request->file('pir_file');
                $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME); // Ambil nama file original tanpa ekstensi
                $extension = $file->getClientOriginalExtension(); // Ambil ekstensi file
                $dateNow = date('dmY'); // Tanggal sekarang dalam format ddmmyyyy
                $version = 0; // Awal versi
                // Format nama file
                $filename = $originalName . '_' . $dateNow . '_' . $version . '.' . $extension;

                // Cek apakah file dengan nama ini sudah ada di folder tujuan
                while (file_exists(public_path("pir/".$filename))) {
                    $version++;
                    $filename = $originalName . '_' . $dateNow . '_' . $version . '.' . $extension;
                }
                // Store file in public/pir/
                $path = $file->move(public_path('pir'), $filename);  
                if (!$path) {
                    return response()->json([
                        'success' => false,
                        'message' => 'File failed upload.',
                    ], 422);
                }
                
                $validatedData['pir_file'] = $filename;                
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
            'pir_file' => 'sometimes|required|file|mimes:pdf',
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
                $file = $request->file('pir_file');
                $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME); // Ambil nama file original tanpa ekstensi
                $extension = $file->getClientOriginalExtension(); // Ambil ekstensi file
                $dateNow = date('dmY'); // Tanggal sekarang dalam format ddmmyyyy
                $version = 0; // Awal versi
                // Format nama file
                $filename = $originalName . '_' . $dateNow . '_' . $version . '.' . $extension;

                // Cek apakah file dengan nama ini sudah ada di folder tujuan
                while (file_exists(public_path("pir/".$filename))) {
                    $version++;
                    $filename = $originalName . '_' . $dateNow . '_' . $version . '.' . $extension;
                }
                // Store file in public/pir/
                $path = $file->move(public_path('pir'), $filename);
                
                if (!$path) {
                    return response()->json([
                        'success' => false,
                        'message' => 'File failed upload.',
                    ], 422);
                }

                // hapus file lama jika ada
                if ($pir->pir_file) {
                    $pirBefore = public_path('pir/' . $pir->pir_file);
                    if (file_exists($pirBefore)) {
                        unlink($pirBefore);
                    }
                }
                
                $validatedData['pir_file'] = $filename;                
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
