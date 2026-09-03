<?php

namespace App\Http\Controllers;

use App\Helpers\FileHelper;
use App\Models\TermBilling;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TermBillingController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $termBilling = TermBilling::with(['termin', 'termin.contract'])->get();

        return response()->json([
            'success' => true,
            'message' => 'Term Billing Status Status retrieved successfully.',
            'data' => $termBilling,
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'termin_id' => 'required|exists:termins,id',
            'billing_value' => 'required|string|max:100',
            'payment_document' => 'required|file|mimes:pdf|max:3072',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi gagal',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validatedData = $validator->validated();

        try {
            $validatedData['payment_document'] = FileHelper::uploadWithVersion($request->file('payment_document'), 'contract/payment');
            $termBilling = TermBilling::create($validatedData);

            return response()->json([
                'success' => true,
                'message' => 'Term Billing Status created successfully.',
                'data' => $termBilling,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create Term Billing Status.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $termBilling = TermBilling::with('termin')->find($id);

        return response()->json([
            'success' => true,
            'message' => 'Term Billing Status retrieved successfully.',
            'data' => $termBilling,
        ], 200);
    }
    public function showByContract(string $id)
    {
        $termBilling = TermBilling::with('termin')
        ->whereHas('termin', function ($query) use ($id) {
            $query->where('contract_id', $id);
        })
        ->get();

        return response()->json([
            'success' => true,
            'message' => 'Term Billing Status retrieved successfully.',
            'data' => $termBilling,
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $termBilling = TermBilling::find($id);
        if (!$termBilling) {
            return response()->json([
                'success' => false,
                'message' => 'Term Billing Status not found.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'termin_id' => 'required|exists:termins,id',
            'billing_value' => 'required|string|max:100',
            'payment_document' => 'sometimes|nullable|file|mimes:pdf|max:3072',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi gagal',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validatedData = $validator->validated();

        try {
            if($request->hasFile('payment_document')){
                if ($termBilling->payment_document) {
                    FileHelper::deleteFile($termBilling->payment_document, 'contract/payment');
                }
                $validatedData['payment_document'] = FileHelper::uploadWithVersion($request->file('payment_document'), 'contract/payment');
            }
            
            if($termBilling->update($validatedData)){
                return response()->json([
                    'success' => true,
                    'message' => 'Term Billing Status updated successfully.',
                    'data' => $termBilling,
                ], 201);
            }else{
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update Term Billing Status.',
                ], 422);
            }
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update Term Billing Status.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $termBilling = TermBilling::find($id);

        if (!$termBilling) {
            return response()->json([
                'success' => false,
                'message' => 'Term Billing Status not found.',
            ], 404);
        }

        try {
            if ($termBilling->payment_document) {
                FileHelper::deleteFile($termBilling->payment_document, 'contract/payment');
            }
            $termBilling->delete();

            return response()->json([
                'success' => true,
                'message' => 'Term Billing Status deleted successfully.',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete Term Billing Status.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }
}
