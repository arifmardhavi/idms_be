<?php

namespace App\Http\Controllers;

use App\Helpers\FileHelper;
use App\Models\HistoricalMemorandum;
use App\Models\Tag_number;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class HistoricalMemorandumController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $historicalMemorandum = HistoricalMemorandum::orderBy('tanggal_terbit', 'desc')->with('unit','category')->get()
        ->map(function ($item) {
            $data = $item->toArray();

            // Parse tag_number_id string into array of IDs
            if (!empty($item->tag_number_id)) {
                $tagNumberIds = explode(',', $item->tag_number_id);
                // Query tag numbers by IDs
                $tagNumbers = Tag_number::whereIn('id', $tagNumberIds)->pluck('tag_number')->toArray();
                $data['tag_numbers'] = $tagNumbers;
            } else {
                $data['tag_numbers'] = [];
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
            'tag_number_id' => 'nullable|string',
            'no_dokumen' => 'required|string|max:255|unique:historical_memorandum,no_dokumen',
            'perihal' => 'required|string|max:255',
            'tipe_memorandum' => 'required',
            'tanggal_terbit' => 'required|date',
            'memorandum_file' => [
                'required',
                'file',
                'max:30720',
                'mimes:pdf',
                'mimetypes:application/pdf'
            ],
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
                $validatedData['memorandum_file'] = FileHelper::uploadWithVersion($request->file('memorandum_file'), 'historical_memorandum');
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
        $historicalMemorandum = HistoricalMemorandum::with('unit','category')->find($id);

        if (!$historicalMemorandum) {
            return response()->json([
                'success' => false,
                'message' => 'Historical Memorandum not found.',
            ], 404);
        }

        // Parse tag_number_id string into array of IDs
        if (!empty($historicalMemorandum->tag_number_id)) {
            $tagNumberIds = explode(',', $historicalMemorandum->tag_number_id);
            // Query tag numbers by IDs
            $tagNumbers = \App\Models\Tag_number::whereIn('id', $tagNumberIds)->pluck('tag_number')->toArray();
            $historicalMemorandum->tag_numbers = $tagNumbers;
        } else {
            $historicalMemorandum->tag_numbers = [];
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
            'tag_number_id' => 'nullable|string',
            'no_dokumen' => 'required|string|max:255|unique:historical_memorandum,no_dokumen,' . $historicalMemorandum->id,
            'perihal' => 'required|string|max:255',
            'tipe_memorandum' => 'required',
            'tanggal_terbit' => 'required|date',
            'memorandum_file' => [
                'nullable',
                'file',
                'max:30720',
                'mimes:pdf',
                'mimetypes:application/pdf'
            ],
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
                if ($historicalMemorandum->memorandum_file) {
                    FileHelper::deleteFile($historicalMemorandum->memorandum_file, 'historical_memorandum');
                }
                $validatedData['memorandum_file'] = FileHelper::uploadWithVersion($request->file('memorandum_file'), 'historical_memorandum');
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
            if ($historicalMemorandum->memorandum_file) {
                FileHelper::deleteFile($historicalMemorandum->memorandum_file, 'historical_memorandum');
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

    public function downloadHistoricalMemorandumFiles(Request $request)
    {
        $ids = $request->input('ids');  // Mendapatkan IDs dari frontend
        
        // Ambil data Historical Memorandum berdasarkan ID yang dipilih
        $historical_memorandums = HistoricalMemorandum::whereIn('id', $ids)->get();
        
        // Buat file ZIP untuk menyimpan memorandum file
        $zip = new \ZipArchive();
        $zipFilePath = public_path('file_historical_memorandum.zip');

        if (file_exists($zipFilePath)) {
            unlink($zipFilePath);
        }
    
        if ($zip->open($zipFilePath, \ZipArchive::CREATE) !== TRUE) {
            return response()->json(['success' => false, 'message' => 'Gagal membuat file ZIP.']);
        }
    
        foreach ($historical_memorandums as $historical_memorandum) {
            // Cek jika file memorandum ada dan file tersebut valid
            if ($historical_memorandum->memorandum_file) {
                $filePath = public_path('historical_memorandum/' . $historical_memorandum->memorandum_file);
                if (file_exists($filePath)) {
                    // Menambahkan file ke dalam ZIP
                    $zip->addFile($filePath, basename($filePath));  
                }
            }
        }
    
        $zip->close();
    
        // Kirimkan URL untuk mendownload file ZIP yang sudah jadi
        activity()->log('download', 'HistoricalMemorandum', [
            'metadata' => ['count' => $historical_memorandums->count()],
        ]);

        return response()->json(['success' => true, 'url' => url('file_historical_memorandum.zip')]);
    }

    public function downloadHistoricalMemorandumFile(string $id)
    {
        $historicalMemorandum = HistoricalMemorandum::find($id);

        if (!$historicalMemorandum) {
            return response()->json([
                'success' => false,
                'message' => 'Historical Memorandum not found.',
            ], 404);
        }

        if (!$historicalMemorandum->memorandum_file) {
            return response()->json([
                'success' => false,
                'message' => 'Historical Memorandum file not found.',
            ], 404);
        }

        activity()->log('download', 'HistoricalMemorandum', [
            'recordId'    => $historicalMemorandum->id,
            'recordLabel' => $historicalMemorandum->no_memo ?? $historicalMemorandum->id,
            'metadata'    => ['file' => $historicalMemorandum->memorandum_file],
        ]);

        return FileHelper::downloadFile('historical_memorandum', $historicalMemorandum->memorandum_file);
    }
}
