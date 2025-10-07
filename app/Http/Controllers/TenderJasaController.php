<?php

namespace App\Http\Controllers;

use App\Models\TenderJasa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TenderJasaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $tender_jasa = TenderJasa::orderBy('id', 'desc')->get();
        return response()->json([
            'success' => true,
            'message' => 'Tender Jasa retrieved successfully.',
            'data' => $tender_jasa,
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'readiness_jasa_id' => 'required|exists:readiness_jasas,id',
            'description' => 'nullable|string',
            'target_date' => 'nullable|date',
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
            $tender_jasa = TenderJasa::create($validatedData);

            return response()->json([
                'success' => true,
                'message' => 'Tender Jasa created successfully.',
                'data' => $tender_jasa,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create Tender Jasa.',
                'error' => $e->getMessage(),
            ], 500);
        }

    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $tender_jasa = TenderJasa::find($id);
        if (!$tender_jasa) {
            return response()->json([
                'success' => false,
                'message' => 'Tender Jasa not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Tender Jasa retrieved successfully.',
            'data' => $tender_jasa,
        ], 200);
    }

    public function showByReadiness(string $id)
    {
        $tender_jasa = TenderJasa::with(['readiness_jasa'])->where('readiness_jasa_id', $id)->orderby('id', 'desc')->get();

        if (!$tender_jasa) {
            return response()->json([
                'success' => false,
                'message' => 'Tender Jasa not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Tender Jasa retrieved successfully.',
            'data' => $tender_jasa,
        ], 200);
        
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $tender_jasa = TenderJasa::find($id);
        if (!$tender_jasa) {
            return response()->json([
                'success' => false,
                'message' => 'Tender Jasa not found.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'readiness_jasa_id' => 'sometimes|required|exists:readiness_jasas,id',
            'description' => 'sometimes|required|string',
            'target_date' => 'sometimes|required|date',
            'status' => 'sometimes|required|integer|in:0,1,2,3', // 0: hijau, 1: biru, 2: kuning, 3: merah
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi gagal',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validatedData = $validator->validated();

        try {
            $tender_jasa->update($validatedData);

            return response()->json([
                'success' => true,
                'message' => 'Tender Jasa updated successfully.',
                'data' => $tender_jasa,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update Tender Jasa.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $tender_jasa = TenderJasa::find($id);
        if (!$tender_jasa) {
            return response()->json([
                'success' => false,
                'message' => 'Tender Jasa not found.',
            ], 404);
        }

        try {
            $tender_jasa->delete();

            return response()->json([
                'success' => true,
                'message' => 'Tender Jasa deleted successfully.',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete Tender Jasa.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
