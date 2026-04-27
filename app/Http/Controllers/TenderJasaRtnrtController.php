<?php

namespace App\Http\Controllers;

use App\Models\TenderJasaRtnrt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TenderJasaRtnrtController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $tender_jasa = TenderJasaRtnrt::orderBy('id', 'desc')->get();
        return response()->json([
            'success' => true,
            'message' => 'Tender Jasa RT/NRT retrieved successfully.',
            'data' => $tender_jasa,
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'readiness_jasa_rtnrt_id' => 'required|exists:readiness_jasa_rtnrts,id',
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
            $tender_jasa = TenderJasaRtnrt::create($validatedData);

            return response()->json([
                'success' => true,
                'message' => 'Tender Jasa RT/NRT created successfully.',
                'data' => $tender_jasa,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create Tender Jasa RT/NRT.',
                'error' => $e->getMessage(),
            ], 500);
        }

    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $tender_jasa = TenderJasaRtnrt::find($id);
        if (!$tender_jasa) {
            return response()->json([
                'success' => false,
                'message' => 'Tender Jasa RT/NRT not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Tender Jasa RT/NRT retrieved successfully.',
            'data' => $tender_jasa,
        ], 200);
    }

    public function showByReadiness(string $id)
    {
        $tender_jasa = TenderJasaRtnrt::with(['readiness_jasa_rtnrt'])->where('readiness_jasa_rtnrt_id', $id)->orderby('id', 'desc')->get();

        if (!$tender_jasa) {
            return response()->json([
                'success' => false,
                'message' => 'Tender Jasa RT/NRT not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Tender Jasa RT/NRT retrieved successfully.',
            'data' => $tender_jasa,
        ], 200);

    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $tender_jasa = TenderJasaRtnrt::find($id);
        if (!$tender_jasa) {
            return response()->json([
                'success' => false,
                'message' => 'Tender Jasa RT/NRT not found.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'readiness_jasa_rtnrt_id' => 'sometimes|required|exists:readiness_jasa_rtnrts,id',
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
                'message' => 'Tender Jasa RT/NRT updated successfully.',
                'data' => $tender_jasa,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update Tender Jasa RT/NRT.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $tender_jasa = TenderJasaRtnrt::find($id);
        if (!$tender_jasa) {
            return response()->json([
                'success' => false,
                'message' => 'Tender Jasa RT/NRT not found.',
            ], 404);
        }

        try {
            $tender_jasa->delete();

            return response()->json([
                'success' => true,
                'message' => 'Tender Jasa RT/NRT deleted successfully.',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete Tender Jasa RT/NRT.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
