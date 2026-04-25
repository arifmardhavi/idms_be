<?php

namespace App\Http\Controllers;

use App\Models\ReadinessJasaOh;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ReadinessJasaOhController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $readiness_jasa = ReadinessJasaOh::with('rekomendasi_jasa_oh.historical_memorandum')->orderBy('id', 'desc')->get();
        return response()->json([
            'success' => true,
            'message' => 'Readiness Jasa OH retrieved successfully.',
            'data' => $readiness_jasa,
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'event_readiness_oh_id' => 'required|exists:event_readiness_ohs,id',
            'jasa_name' => 'required|string|max:100',
            'price_estimate' => 'nullable|integer',
            'tanggal_target' => 'required|date',
            'status' => 'nullable|integer|in:0,1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi gagal',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validatedData = $validator->validated();

        try {
            $readiness_jasa = ReadinessJasaOh::create($validatedData);

            return response()->json([
                'success' => true,
                'message' => 'Readiness Jasa OH created successfully.',
                'data' => $readiness_jasa,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create Readiness Jasa OH.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $readiness_jasa = ReadinessJasaOh::find($id);

        if (!$readiness_jasa) {
            return response()->json([
                'success' => false,
                'message' => 'Readiness Jasa OH not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Readiness Jasa OH retrieved successfully.',
            'data' => $readiness_jasa,
        ], 200);
    }

    public function showByEvent(string $id)
    {
        $readiness_jasa = ReadinessJasaOh::with('rekomendasi_jasa_oh.historical_memorandum')->where('event_readiness_oh_id', $id)->get();

        if ($readiness_jasa->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Readiness Jasa OH not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Readiness Jasa OH retrieved successfully.',
            'data' => $readiness_jasa,
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $readiness_jasa = ReadinessJasaOh::find($id);

        if (!$readiness_jasa) {
            return response()->json([
                'success' => false,
                'message' => 'Readiness Jasa OH not found.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'jasa_name' => 'sometimes|string|max:100',
            'price_estimate' => 'sometimes|nullable|integer',
            'tanggal_target' => 'sometimes|date',
            'status' => 'sometimes|integer|in:0,1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi gagal',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validatedData = $validator->validated();

        try {
            $readiness_jasa->update($validatedData);

            return response()->json([
                'success' => true,
                'message' => 'Readiness Jasa OH updated successfully.',
                'data' => $readiness_jasa,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update Readiness Jasa OH.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $readiness_jasa = ReadinessJasaOh::find($id);

        if (!$readiness_jasa) {
            return response()->json([
                'success' => false,
                'message' => 'Readiness Jasa OH not found.',
            ], 404);
        }

        try {
            $readiness_jasa->delete();

            return response()->json([
                'success' => true,
                'message' => 'Readiness Jasa OH deleted successfully.',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete Readiness Jasa OH.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }

    public function updateStatus(Request $request, string $id )
    {
        $readiness_jasa = ReadinessJasaOh::find($id);
        if (!$readiness_jasa) {
            return response()->json([
                'success' => false,
                'message' => 'Readiness Jasa OH not found.',
            ], 404);
        }
        $validator = Validator::make($request->all(), [
            'status' => 'sometimes|integer|in:0,1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed for current status',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validatedData = $validator->validated();
        $readiness_jasa->update($validatedData);

        return response()->json([
            'success' => true,
            'message' => 'Status updated successfully.',
            'data' => $readiness_jasa,
        ], 200);
    }
}
