<?php

namespace App\Http\Controllers;

use App\Helpers\FileHelper;
use App\Models\SpkNew;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SpkNewController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $spk = SpkNew::all();

        return response()->json([
            'success' => true,
            'message' => 'spk retrieved successfully.',
            'data' => $spk,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'contract_new_id' => 'required|exists:contract_news,id',
            'no_spk' => 'required|string|max:100',
            'spk_name' => 'required|string|max:200',
            'spk_start_date' => 'required|date',
            'spk_end_date' => 'required|date',
            'spk_price' => 'required|integer',
            'spk_file' => 'required|file|mimes:pdf|max:25600', // Maksimal 25MB
            'spk_status' => 'required|in:0,1',
            'receipt_nominal' => 'nullable|integer',
            'receipt_file' => 'nullable|file|mimes:pdf|max:3072', // Maksimal 3MB
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi gagal',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validatedData = $validator->validated();
        $validatedData['spk_file'] = FileHelper::uploadWithVersion($request->file('spk_file'), 'contract_new/spk');
        if ($request->hasFile('receipt_file')){
            $validatedData['receipt_file'] = FileHelper::uploadWithVersion($request->file('receipt_file'), 'contract_new/spk/receipt');
        }

        $spk = SpkNew::create($validatedData);

        return response()->json([
            'success' => true,
            'message' => 'spk created successfully.',
            'data' => $spk,
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $spk = SpkNew::find($id);

        if (!$spk) {
            return response()->json([
                'success' => false,
                'message' => 'spk not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'spk retrieved successfully.',
            'data' => $spk,
        ]);
    }
    public function showByContract(string $id)
    {
        $spk = SpkNew::where('contract_new_id', $id)->get();

        if (!$spk) {
            return response()->json([
                'success' => false,
                'message' => 'spk not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'spk retrieved successfully.',
            'data' => $spk,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $spk = SpkNew::find($id);

        if (!$spk) {
            return response()->json([
                'success' => false,
                'message' => 'spk not found.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'contract_new_id' => 'required|exists:contract_news,id',
            'no_spk' => 'required|string|max:100',
            'spk_name' => 'required|string|max:200',
            'spk_start_date' => 'required|date',
            'spk_end_date' => 'required|date',
            'spk_price' => 'required|integer',
            'spk_file' => 'required|file|mimes:pdf|max:25600', // Maksimal 25MB
            'spk_status' => 'required|in:0,1',
            'receipt_nominal' => 'nullable|integer',
            'receipt_file' => 'nullable|file|mimes:pdf|max:3072', // Maksimal 3MB
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi gagal',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validatedData = $validator->validated();

        if ($request->hasFile('spk_file')){
            $validatedData['spk_file'] = FileHelper::uploadWithVersion($request->file('spk_file'), 'contract_new/spk');
            if ($spk->spk_file) {
                FileHelper::deleteFile($spk->spk_file, 'contract_new/spk');
            }
        }
        if ($request->hasFile('receipt_file')){
            $validatedData['receipt_file'] = FileHelper::uploadWithVersion($request->file('receipt_file'), 'contract_new/spk/receipt');
            if ($spk->receipt_file) {
                FileHelper::deleteFile($spk->receipt_file, 'contract_new/spk/receipt');
            }
        }

        $spk->update($validatedData);

        return response()->json([
            'success' => true,
            'message' => 'spk updated successfully.',
            'data' => $spk,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $spk = SpkNew::find($id);

        if (!$spk) {
            return response()->json([
                'success' => false,
                'message' => 'spk not found.',
            ], 404);
        }

        try {
            if ($spk->spk_file) {
                FileHelper::deleteFile($spk->spk_file, 'contract_new/spk');
            }
            if ($spk->receipt_file) {
                FileHelper::deleteFile($spk->receipt_file, 'contract_new/spk/receipt');
            }
            $spk->delete();
            return response()->json([
                'success' => true,
                'message' => 'spk deleted successfully.',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'spk deleted failed.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
