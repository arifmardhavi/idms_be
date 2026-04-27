<?php

namespace App\Http\Controllers;

use App\Models\ContractJasaRtnrt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ContractJasaRtnrtController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $contract_jasa = ContractJasaRtnrt::orderBy('id', 'desc')->get();
        return response()->json([
            'success' => true,
            'message' => 'Contract Jasa RT/NRT retrieved successfully.',
            'data' => $contract_jasa,
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'readiness_jasa_rtnrt_id' => 'required|exists:readiness_jasa_rtnrts,id',
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
            $contract_jasa = ContractJasaRtnrt::create($validatedData);

            return response()->json([
                'success' => true,
                'message' => 'Contract Jasa RT/NRT created successfully.',
                'data' => $contract_jasa,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create contract Jasa RT/NRT.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $contract_jasa = ContractJasaRtnrt::find($id);

        if (!$contract_jasa) {
            return response()->json([
                'success' => false,
                'message' => 'Contract Jasa RT/NRT not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Contract Jasa RT/NRT retrieved successfully.',
            'data' => $contract_jasa,
        ]);

    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $contract_jasa = ContractJasaRtnrt::find($id);

        if (!$contract_jasa) {
            return response()->json([
                'success' => false,
                'message' => 'Contract Jasa RT/NRT not found.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'readiness_jasa_rtnrt_id' => 'sometimes|exists:readiness_jasa_rtnrts,id',
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
                'message' => 'Contract Jasa RT/NRT updated successfully.',
                'data' => $contract_jasa,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update contract Jasa RT/NRT.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $contract_jasa = ContractJasaRtnrt::find($id);

        if (!$contract_jasa) {
            return response()->json([
                'success' => false,
                'message' => 'Contract Jasa RT/NRT not found.',
            ], 404);
        }

        try {
            $contract_jasa->delete();

            return response()->json([
                'success' => true,
                'message' => 'Contract Jasa RT/NRT deleted successfully.',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete contract Jasa RT/NRT.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
