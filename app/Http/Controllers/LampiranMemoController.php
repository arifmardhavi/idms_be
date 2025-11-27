<?php

namespace App\Http\Controllers;

use App\Models\LampiranMemo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use SebastianBergmann\CodeCoverage\Report\Xml\Report;

class LampiranMemoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $lampiranMemo = LampiranMemo::orderBy('id', 'desc')->get();

        return response()->json([
            'success' => true,
            'message' => 'Lampiran Memorandum retrieved successfully.',
            'data' => $lampiranMemo,
        ], 200);
    }

    public function showWithHistoricalId($id)
    {
        $lampiranMemo = LampiranMemo::with(['historicalMemorandum'])->where('historical_memorandum_id', $id)->get();

        if ($lampiranMemo->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Lampiran Memorandum not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Lampiran Memorandum retrieved successfully.',
            'data' => $lampiranMemo,
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'historical_memorandum_id' => 'required|exists:historical_memorandum,id',
            'lampiran_memo' => 'required|array',
            'lampiran_memo.*' => 'file|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png,zip,rar|max:204800',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi Lampiran Memorandum gagal',
                'errors' => $validator->errors(),
            ], 422);
        }

        if (count($request->file('lampiran_memo')) > 10) {
            return response()->json([
                'success' => false,
                'message' => 'Maksimal upload 10 file.',
            ], 422);
        }

        try {
            $result = [];
            $failedFiles = [];

            foreach ($request->file('lampiran_memo') as $file) {
                $originalName = $file->getClientOriginalName();

                try {
                    $nameOnly = pathinfo($originalName, PATHINFO_FILENAME);
                    $extension = $file->getClientOriginalExtension();
                    $dateNow = date('dmY');
                    $version = 0;

                    $filename = $nameOnly . '_' . $dateNow . '_' . $version . '.' . $extension;
                    while (file_exists(public_path("historical_memorandum/lampiran/" . $filename))) {
                        $version++;
                        $filename = $nameOnly . '_' . $dateNow . '_' . $version . '.' . $extension;
                    }

                    $path = $file->move(public_path('historical_memorandum/lampiran'), $filename);
                    if (!$path) {
                        $failedFiles[] = [
                            'name' => $originalName,
                            'error' => 'Gagal memindahkan file ke direktori tujuan.'
                        ];
                        continue;
                    }

                    $lampiran = LampiranMemo::create([
                        'historical_memorandum_id' => $request->historical_memorandum_id,
                        'lampiran_memo' => $filename,
                    ]);

                    $result[] = $lampiran;

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
                'message' => 'Gagal upload lampiran.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }


    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $lampiranMemo = LampiranMemo::with(['historicalMemorandum'])->find($id);

        if (!$lampiranMemo) {
            return response()->json([
                'success' => false,
                'message' => 'Lampiran Memorandum not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Lampiran Memorandum retrieved successfully.',
            'data' => $lampiranMemo,
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $lampiranMemo = LampiranMemo::find($id);

        if (!$lampiranMemo) {
            return response()->json([
                'success' => false,
                'message' => 'Lampiran Memorandum not found.',
            ], 404);
        }

        try {
            if ($lampiranMemo->lampiran_memo) {
                $path = public_path('historical_memorandum/lampiran/' . $lampiranMemo->lampiran_memo);
                if (file_exists($path)) {
                    unlink($path); // Hapus file
                }
            }
            if($lampiranMemo->delete()){
                return response()->json([
                    'success' => true,
                    'message' => 'Lampiran Memorandum deleted successfully.',
                ], 200);
            }else{
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to delete Lampiran Memorandum.',
                ], 422);
            }
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete Lampiran Memorandum.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }

    public function downloadLampiranMemoFiles(Request $request)
    {
        $ids = $request->input('ids');  // Mendapatkan IDs dari frontend
        
        // Ambil data Historical Memorandum berdasarkan ID yang dipilih
        $lampiranMemos = LampiranMemo::whereIn('id', $ids)->get();
        
        // Buat file ZIP untuk menyimpan memorandum file
        $zip = new \ZipArchive();
        $zipFilePath = public_path('file_lampiran_memo.zip');

        if (file_exists($zipFilePath)) {
            unlink($zipFilePath);
        }
    
        if ($zip->open($zipFilePath, \ZipArchive::CREATE) !== TRUE) {
            return response()->json(['success' => false, 'message' => 'Gagal membuat file ZIP.']);
        }
    
        foreach ($lampiranMemos as $lampiranMemo) {
            // Cek jika file lampiran memorandum ada dan file tersebut valid
            if ($lampiranMemo->lampiran_memo) {
                $filePath = public_path('historical_memorandum/lampiran/' . $lampiranMemo->lampiran_memo);
                if (file_exists($filePath)) {
                    // Menambahkan file ke dalam ZIP
                    $zip->addFile($filePath, basename($filePath));  
                }
            }
        }
    
        $zip->close();
    
        // Kirimkan URL untuk mendownload file ZIP yang sudah jadi
        return response()->json(['success' => true, 'url' => url('file_lampiran_memo.zip')]);
    }
}
