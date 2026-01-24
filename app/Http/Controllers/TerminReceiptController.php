<?php

namespace App\Http\Controllers;

use App\Helpers\FileHelper;
use App\Models\TerminReceiptNew;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TerminReceiptController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $termin_receipt = TerminReceiptNew::all();

        return response()->json([
            'success' => true,
            'message' => 'Termin Receipt retrieved successfully.',
            'data' => $termin_receipt,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'termin_new_id' => 'required|exists:termin_news,id',
            'receipt_nominal' => 'required|integer',
            'receipt_file' => 'required|file|mimes:pdf|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi gagal',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validatedData = $validator->validated();

        if ($request->hasFile('receipt_file')) {
            $validatedData['receipt_file'] = FileHelper::uploadWithVersion($request->file('receipt_file'), 'contract_new/lumpsum/receipt');
        }

        $termin_receipt = TerminReceiptNew::create($validatedData);

        return response()->json([
            'success' => true,
            'message' => 'Termin Receipt created successfully.',
            'data' => $termin_receipt,
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $termin_receipt = TerminReceiptNew::find($id);

        if (!$termin_receipt) {
            return response()->json([
                'success' => false,
                'message' => 'Termin Receipt not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Termin Receipt retrieved successfully.',
            'data' => $termin_receipt,
        ]);
    }
    public function showByTermin(string $id)
    {
        $termin_receipt = TerminReceiptNew::where('termin_new_id', $id)->get();

        if (!$termin_receipt) {
            return response()->json([
                'success' => false,
                'message' => 'Termin Receipt not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Termin Receipt retrieved successfully.',
            'data' => $termin_receipt,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $termin_receipt = TerminReceiptNew::find($id);

        if (!$termin_receipt) {
            return response()->json([
                'success' => false,
                'message' => 'Termin Receipt not found.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'receipt_nominal' => 'sometimes|required|integer',
            'receipt_file' => 'sometimes|nullable|file|mimes:pdf|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi gagal',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validatedData = $validator->validated();

        if ($request->hasFile('receipt_file')) {
            $validatedData['receipt_file'] = FileHelper::uploadWithVersion($request->file('receipt_file'), 'contract_new/receipt');
            if ($termin_receipt->receipt_file) {
                FileHelper::deleteFile($termin_receipt->receipt_file, 'contract_new/receipt');
            }
        }

        $termin_receipt->update($validatedData);

        return response()->json([
            'success' => true,
            'message' => 'Termin Receipt updated successfully.',
            'data' => $termin_receipt,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $termin_receipt = TerminReceiptNew::find($id);

        if (!$termin_receipt) {
            return response()->json([
                'success' => false,
                'message' => 'Termin Receipt not found.',
            ], 404);
        }

        try {
            if ($termin_receipt->receipt_file) {
                FileHelper::deleteFile($termin_receipt->receipt_file, 'contract_new/receipt');
            }
            $termin_receipt->delete();
            return response()->json([
                'success' => true,
                'message' => 'Termin Receipt deleted successfully.',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Termin Receipt deleted failed.',
            ], 500);
        }
    }
}
