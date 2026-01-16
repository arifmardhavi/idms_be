<?php

namespace App\Http\Controllers;

use App\Models\IzinUsaha;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class IzinUsahaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $izin_usaha =  IzinUsaha::orderBy('tanggal_izin_usaha', 'desc')->get();
        return response()->json([
            'success' => true,
            'message' => 'Izin Usaha retrieved successfully.',
            'data' => $izin_usaha,
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'no_izin_usaha' => 'required|string|max:200|unique:izin_usahas,no_izin_usaha',
            'judul' => 'required|string|max:255',
            'tanggal_izin_usaha' => 'required|date',
            'izin_usaha_file' => 'required|file|mimes:pdf',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed for Izin Usaha',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validatedData = $validator->validated();

        try {
            if ($request->hasFile('izin_usaha_file')) {
                $file = $request->file('izin_usaha_file');
                $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME); // Ambil nama file original tanpa ekstensi
                $extension = $file->getClientOriginalExtension(); // Ambil ekstensi file
                $dateNow = date('dmY'); // Tanggal sekarang dalam format ddmmyyyy
                $version = 0; // Awal versi
                // Format nama file
                $filename = $originalName . '_' . $dateNow . '_' . $version . '.' . $extension;

                // Cek apakah file dengan nama ini sudah ada di folder tujuan
                while (file_exists(public_path("izin_usaha/".$filename))) {
                    $version++;
                    $filename = $originalName . '_' . $dateNow . '_' . $version . '.' . $extension;
                }
                // Store file in public/izin_usaha/
                $path = $file->move(public_path('izin_usaha'), $filename);  
                if (!$path) {
                    return response()->json([
                        'success' => false,
                        'message' => 'File failed upload.',
                    ], 422);
                }
                
                $validatedData['izin_usaha_file'] = $filename;                
            }

            $izin_usaha = IzinUsaha::create($validatedData);

            return response()->json([
                'success' => true,
                'message' => 'Izin Usaha created successfully.',
                'data' => $izin_usaha,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create Izin Usaha.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $izinUsaha = IzinUsaha::find($id);
        if (!$izinUsaha) {
            return response()->json([
                'success' => false,
                'message' => 'Izin Usaha not found.',
            ], 404);
        }
        return response()->json([
            'success' => true,
            'message' => 'Izin Usaha retrieved successfully.',
            'data' => $izinUsaha,
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $izin_usaha = IzinUsaha::find($id);
        if (!$izin_usaha) {
            return response()->json([
                'success' => false,
                'message' => 'Izin Usaha not found.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'no_izin_usaha' => 'sometimes|required|string|max:200|unique:izin_usahas,no_izin_usaha,' . $id,
            'judul' => 'sometimes|required|string|max:255',
            'tanggal_izin_usaha' => 'sometimes|required|date',
            'izin_usaha_file' => 'sometimes|required|file|mimes:pdf',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed for Izin Usaha',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validatedData = $validator->validated();

        try {
            if ($request->hasFile('izin_usaha_file')) {
                $file = $request->file('izin_usaha_file');
                $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME); // Ambil nama file original tanpa ekstensi
                $extension = $file->getClientOriginalExtension(); // Ambil ekstensi file
                $dateNow = date('dmY'); // Tanggal sekarang dalam format ddmmyyyy
                $version = 0; // Awal versi
                // Format nama file
                $filename = $originalName . '_' . $dateNow . '_' . $version . '.' . $extension;

                // Cek apakah file dengan nama ini sudah ada di folder tujuan
                while (file_exists(public_path("izin_usaha/".$filename))) {
                    $version++;
                    $filename = $originalName . '_' . $dateNow . '_' . $version . '.' . $extension;
                }
                // Store file in public/izin_usaha/
                $path = $file->move(public_path('izin_usaha'), $filename);
                
                if (!$path) {
                    return response()->json([
                        'success' => false,
                        'message' => 'File failed upload.',
                    ], 422);
                }

                // hapus file lama jika ada
                if ($izin_usaha->izin_usaha_file) {
                    $izinUsahaBefore = public_path('izin_usaha/' . $izin_usaha->izin_usaha_file);
                    if (file_exists($izinUsahaBefore)) {
                        unlink($izinUsahaBefore);
                    }
                }
                
                $validatedData['izin_usaha_file'] = $filename;                
            }

            $izin_usaha->update($validatedData);

            return response()->json([
                'success' => true,
                'message' => 'Izin Usaha updated successfully.',
                'data' => $izin_usaha,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update Izin Usaha.',
                'errors' => $e->getMessage(),
            ], 500);
        }


    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $izin_usaha = IzinUsaha::find($id);
        if (!$izin_usaha) {
            return response()->json([
                'success' => false,
                'message' => 'Izin Usaha not found.',
            ], 404);
        }

        try {
            if ($izin_usaha->izin_usaha_file) {
                $filePath = public_path('izin_usaha/' . $izin_usaha->izin_usaha_file);
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
            }
            $izin_usaha->delete();
            return response()->json([
                'success' => true,
                'message' => 'Izin Usaha deleted successfully.',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete Izin Usaha.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }
}
