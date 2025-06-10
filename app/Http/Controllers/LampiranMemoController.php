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
            'lampiran_memo' => 'required|file|mimes:pdf',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi Lampiran Memorandum gagal',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validatedData = $validator->validated();
        try {
            if ($request->hasFile('lampiran_memo')) {
                $file = $request->file('lampiran_memo');
                $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME); // Ambil nama file original tanpa ekstensi
                $extension = $file->getClientOriginalExtension(); // Ambil ekstensi file
                $dateNow = date('dmY'); // Tanggal sekarang dalam format ddmmyyyy
                $version = 0; // Awal versi
                $filename = $originalName . '_' . $dateNow . '_' . $version . '.' . $extension; // Nama file baru dengan versi
                while (file_exists(public_path("historical_memorandum/lampiran/".$filename))) {
                    $version++; // Increment versi
                    $filename = $originalName . '_' . $dateNow . '_' . $version . '.' . $extension; // Nama file baru dengan versi baru
                }
                $path = $file->move(public_path('historical_memorandum/lampiran'), $filename);
                if(!$path){
                    return response()->json([
                        'success' => false,
                        'message' => 'Lampiran Memorandum failed upload.',
                    ], 422);
                }  
                $validatedData['lampiran_memo'] = $filename;
            }
            $lampiranMemo = LampiranMemo::create($validatedData);
            if($lampiranMemo){
                return response()->json([
                    'success' => true,
                    'message' => 'Lampiran Memorandum created successfully.',
                    'data' => $lampiranMemo,
                ], 201);
            }else{
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create Lampiran Memorandum.',
                ], 422);
            }
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create Lampiran Memorandum.',
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
}
