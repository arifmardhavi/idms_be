<?php

namespace App\Http\Controllers;

use App\Models\ContractJasaOh;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ContractJasaOhController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $contract_jasa = ContractJasaOh::orderBy('id', 'desc')->get();
        return response()->json([
            'success' => true,
            'message' => 'Contract Jasa OH retrieved successfully.',
            'data' => $contract_jasa,
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'readiness_jasa_oh_id' => 'required|exists:readiness_jasa_ohs,id',
            'contract_new_id' => 'nullable|exists:contract_news,id',
            'status' => 'nullable|integer|in:0,1,2,3', // 0: hijau, 1: biru, 2: kuning, 3: merah
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi gagal',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validatedData = $validator->validated();

        try {
            $contract_jasa = ContractJasaOh::create($validatedData);

            return response()->json([
                'success' => true,
                'message' => 'Contract Jasa OH created successfully.',
                'data' => $contract_jasa,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create contract Jasa OH.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $contract_jasa = ContractJasaOh::find($id);

        if (!$contract_jasa) {
            return response()->json([
                'success' => false,
                'message' => 'Contract Jasa OH not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Contract Jasa OH retrieved successfully.',
            'data' => $contract_jasa,
        ]);

    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $contract_jasa = ContractJasaOh::find($id);

        if (!$contract_jasa) {
            return response()->json([
                'success' => false,
                'message' => 'Contract Jasa OH not found.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'readiness_jasa_oh_id' => 'sometimes|exists:readiness_jasa_ohs,id',
            'contract_new_id' => 'sometimes|nullable|exists:contract_news,id',
            'status' => 'nullable|integer|in:0,1,2,3', // 0: hijau, 1: biru, 2: kuning, 3: merah
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi gagal',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validatedData = $validator->validated();

        try {
            $contract_jasa->update($validatedData);
            return response()->json([
                'success' => true,
                'message' => 'Contract Jasa OH updated successfully.',
                'data' => $contract_jasa,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update contract Jasa OH.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $contract_jasa = ContractJasaOh::find($id);

        if (!$contract_jasa) {
            return response()->json([
                'success' => false,
                'message' => 'Contract Jasa OH not found.',
            ], 404);
        }

        try {
            $contract_jasa->delete();

            return response()->json([
                'success' => true,
                'message' => 'Contract Jasa OH deleted successfully.',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete contract Jasa OH.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
