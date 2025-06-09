<?php

namespace App\Http\Controllers;

use App\Models\HistoricalMemorandum;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class HistoricalMemorandumController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $historicalMemorandum = HistoricalMemorandum::with('unit','category','tag_number')->get()
        ->map(function ($item) {
            $data = $item->toArray();
            if ($item->tag_number === null) {
                unset($data['tag_number']);
            }
            return $data;
        });

        return response()->json([
            'success' => true,
            'message' => 'Historical Memorandum retrieved successfully.',
            'data' => $historicalMemorandum,
        ], 200);


    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'unit_id' => 'required',
            'category_id' => 'required|exists:categories,id',
            'tag_number_id' => 'nullable|exists:tag_numbers,id',
            'no_dokumen' => 'required|string|max:255|unique:historical_memorandum,no_dokumen',
            'perihal' => 'required|string|max:255',
            'tipe_memorandum' => 'required',
            'tanggal_terbit' => 'required|date',
            'memorandum_file' => 'required|file|mimes:pdf|max:30720', // 30 MB
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi gagal',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validatedData = $validator->validated();
        try {
            if ($request->hasFile('memorandum_file')) {
                $file = $request->file('memorandum_file');
                $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME); // Ambil nama file original tanpa ekstensi
                $extension = $file->getClientOriginalExtension(); // Ambil ekstensi file
                $dateNow = date('dmY'); // Tanggal sekarang dalam format ddmmyyyy
                $version = 0; // Awal versi
                // Format nama file
                $filename = $originalName . '_' . $dateNow . '_' . $version . '.' . $extension;

                // Cek apakah file dengan nama ini sudah ada di folder tujuan
                while (file_exists(public_path("historical_memorandum/".$filename))) {
                    $version++;
                    $filename = $originalName . '_' . $dateNow . '_' . $version . '.' . $extension;
                }
                $path = $file->move(public_path('historical_memorandum'), $filename);
                $validatedData['memorandum_file'] = $filename;
            }
            $historicalMemorandum = HistoricalMemorandum::create($validatedData);
            return response()->json([
                'success' => true,
                'message' => 'Historical Memorandum created successfully.',
                'data' => $historicalMemorandum,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Historical Memorandum created failed.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $historicalMemorandum = HistoricalMemorandum::with('unit','category','tag_number')->find($id);

        if (!$historicalMemorandum) {
            return response()->json([
                'success' => false,
                'message' => 'Historical Memorandum not found.',
            ], 404);
        }

        // Sembunyikan relasi jika null
        if (is_null($historicalMemorandum->tag_number)) {
            $historicalMemorandum->makeHidden(['tag_number']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Historical Memorandum retrieved successfully.',
            'data' => $historicalMemorandum,
        ], 200);

    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $historicalMemorandum = HistoricalMemorandum::find($id);
        if (!$historicalMemorandum) {
            return response()->json([
                'success' => false,
                'message' => 'Historical Memorandum not found.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'unit_id' => 'required',
            'category_id' => 'required|exists:categories,id',
            'tag_number_id' => 'nullable|exists:tag_numbers,id',
            'no_dokumen' => 'required|string|max:255|unique:historical_memorandum,no_dokumen,' . $historicalMemorandum->id,
            'perihal' => 'required|string|max:255',
            'tipe_memorandum' => 'required',
            'tanggal_terbit' => 'required|date',
            'memorandum_file' => 'nullable|file|mimes:pdf|max:30720', // 30 MB
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi gagal',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validatedData = $validator->validated();
        try {
            if ($request->hasFile('memorandum_file')) {
                $file = $request->file('memorandum_file');
                $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME); // Ambil nama file original tanpa ekstensi
                $extension = $file->getClientOriginalExtension(); // Ambil ekstensi file
                $dateNow = date('dmY'); // Tanggal sekarang dalam format ddmmyyyy
                $version = 0; // Awal versi
                // Format nama file
                $filename = $originalName . '_' . $dateNow . '_' . $version . '.' . $extension;

                // Cek apakah file dengan nama ini sudah ada di folder tujuan
                while (file_exists(public_path("historical_memorandum/" .$filename))) {
                    $version++;
                    $filename = $originalName . '_' . $dateNow . '_' . $version . '.' . $extension;
                }
                // Hapus file lama jika ada
                if (file_exists(public_path("historical_memorandum/" .$historicalMemorandum->memorandum_file))) {
                    unlink(public_path("historical_memorandum/" .$historicalMemorandum->memorandum_file));
                }
                $path = $file->move(public_path('historical_memorandum'), $filename);
                $validatedData['memorandum_file'] = $filename;
            }
            $historicalMemorandum->update($validatedData);
            return response()->json([
                'success' => true,
                'message' => 'Historical Memorandum updated successfully.',
                'data' => $historicalMemorandum,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Historical Memorandum updated failed.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $historicalMemorandum = HistoricalMemorandum::find($id);
        if (!$historicalMemorandum) {
            return response()->json([
                'success' => false,
                'message' => 'Historical Memorandum not found.',
            ], 404);
        }

        try {
            // Hapus file jika ada
            if (file_exists(public_path("historical_memorandum/" .$historicalMemorandum->memorandum_file))) {
                unlink(public_path("historical_memorandum/" .$historicalMemorandum->memorandum_file));
            }
            $historicalMemorandum->delete();
            return response()->json([
                'success' => true,
                'message' => 'Historical Memorandum deleted successfully.',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Historical Memorandum deleted failed.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
