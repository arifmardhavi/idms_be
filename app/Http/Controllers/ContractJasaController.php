<?php

namespace App\Http\Controllers;

use App\Models\ContractJasa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ContractJasaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $contract_jasa = ContractJasa::orderBy('id', 'desc')->get();
        return response()->json([
            'success' => true,
            'message' => 'Contract jasa retrieved successfully.',
            'data' => $contract_jasa,
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'readiness_jasa_id' => 'required|exists:readiness_jasas,id',
            'contract_id' => 'nullable|exists:contracts,id',
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
            $contract_jasa = ContractJasa::create($validatedData);

            return response()->json([
                'success' => true,
                'message' => 'Contract jasa created successfully.',
                'data' => $contract_jasa,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create contract jasa.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $contract_jasa = ContractJasa::find($id);

        if (!$contract_jasa) {
            return response()->json([
                'success' => false,
                'message' => 'Contract jasa not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Contract jasa retrieved successfully.',
            'data' => $contract_jasa,
        ]);

    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $contract_jasa = ContractJasa::find($id);

        if (!$contract_jasa) {
            return response()->json([
                'success' => false,
                'message' => 'Contract jasa not found.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'readiness_jasa_id' => 'sometimes|exists:readiness_jasas,id',
            'contract_id' => 'sometimes|nullable|exists:contracts,id',
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
                'message' => 'Contract jasa updated successfully.',
                'data' => $contract_jasa,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update contract jasa.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $contract_jasa = ContractJasa::find($id);

        if (!$contract_jasa) {
            return response()->json([
                'success' => false,
                'message' => 'Contract jasa not found.',
            ], 404);
        }

        try {
            if ($contract_jasa->contract_file && file_exists(public_path("readiness_ta/jasa/contract/" . $contract_jasa->contract_file))) {
                unlink(public_path("readiness_ta/jasa/contract/" . $contract_jasa->contract_file));
            }
            $contract_jasa->delete();

            return response()->json([
                'success' => true,
                'message' => 'Contract jasa deleted successfully.',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete contract jasa.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
