<?php

namespace App\Http\Controllers;

use App\Helpers\FileHelper;
use App\Models\Spk;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class SpkController extends Controller
{

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $spk = Spk::with('contract')->get();

        return response()->json([
            'success' => true,
            'message' => 'spk retrieved successfully.',
            'data' => $spk,
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'contract_id' => 'required|exists:contracts,id',
            'no_spk' => 'required|string|max:100',
            'spk_name' => 'required|string|max:200',
            'spk_start_date' => 'required|date',
            'spk_end_date' => 'required|date',
            'spk_price' => 'required|integer',
            'spk_file' => 'required|file|mimes:pdf|max:25600',
            'spk_status' => 'required|in:0,1',
            'invoice' => 'required|in:0,1',
            'invoice_value' => 'nullable|integer',
            'invoice_file' => 'nullable|file|mimes:pdf|max:3072',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi gagal',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validatedData = $validator->validated();

        try {
            $validatedData['spk_file'] = FileHelper::uploadWithVersion($request->file('spk_file'), 'contract/spk');

            if ($request->hasFile('invoice_file')) {
                $validatedData['invoice_file'] = FileHelper::uploadWithVersion($request->file('invoice_file'), 'contract/spk/invoice');
            }
            $spk = Spk::create($validatedData);

            return response()->json([
                'success' => true,
                'message' => 'spk created successfully.',
                'data' => $spk,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create spk.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $spk = Spk::with('contract')->find($id);

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
        ], 200);
    }

    public function showByContract(string $id)
    {
        $spkList = Spk::where('contract_id', $id)->with('contract')->get();

        if ($spkList->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'SPK not found.',
            ], 404);
        }
    
        return response()->json([
            'success' => true,
            'message' => 'SPK retrieved successfully.',
            'data' => $spkList,
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $spk = Spk::find($id);

        if (!$spk) {
            return response()->json([
                'success' => false,
                'message' => 'spk not found.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'contract_id' => 'required|exists:contracts,id',
            'no_spk' => 'required|string|max:100',
            'spk_name' => 'required|string|max:200',
            'spk_start_date' => 'required|date',
            'spk_end_date' => 'required|date',
            'spk_price' => 'required|integer',
            'spk_file' => 'nullable|file|mimes:pdf|max:25600',
            'spk_status' => 'required|in:0,1',
            'invoice' => 'required|in:0,1',
            'invoice_value' => 'nullable|integer',
            'invoice_file' => 'nullable|file|mimes:pdf|max:3072',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi gagal',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validatedData = $validator->validated();

        try {
            if($request->hasFile('spk_file')){
                if($spk->spk_file){
                    FileHelper::deleteFile($spk->spk_file, 'contract/spk');
                }
                $validatedData['spk_file'] = FileHelper::uploadWithVersion($request->file('spk_file'), 'contract/spk');
            }
            if ($request->hasFile('invoice_file')) {
                if($spk->invoice_file){
                    FileHelper::deleteFile($spk->invoice_file, 'contract/spk/invoice');
                }
                $validatedData['invoice_file'] = FileHelper::uploadWithVersion($request->file('invoice_file'), 'contract/spk/invoice');
            }
            $spk->update($validatedData);

            return response()->json([
                'success' => true,
                'message' => 'spk updated successfully.',
                'data' => $spk,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update spk.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $spk = Spk::find($id);

        if (!$spk) {
            return response()->json([
                'success' => false,
                'message' => 'spk not found.',
            ], 404);
        }

        try {
            if($spk->spk_file){
                FileHelper::deleteFile($spk->spk_file, 'contract/spk');
            }
            if($spk->invoice_file){
                FileHelper::deleteFile($spk->invoice_file, 'contract/spk/invoice');
            }
            $spk->delete();

            return response()->json([
                'success' => true,
                'message' => 'spk deleted successfully.',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete spk.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }
}
