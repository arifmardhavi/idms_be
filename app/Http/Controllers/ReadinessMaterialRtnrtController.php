<?php

namespace App\Http\Controllers;

use App\Models\ReadinessMaterialRtnrt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ReadinessMaterialRtnrtController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $readiness_material = ReadinessMaterialRtnrt::orderBy('id', 'desc')->get();
        return response()->json([
            'success' => true,
            'message' => 'Readiness Material RT/NRT retrieved successfully.',
            'data' => $readiness_material,
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'event_readiness_rtnrt_id' => 'required|exists:event_readiness_rtnrts,id',
            'material_name' => 'required|string|max:100',
            'price_estimate' => 'nullable|integer',
            'type' => 'nullable|integer|in:0,1', // 0: LLDI, 1: Non LLDI
            'tanggal_target' => 'required|date',
            'status' => 'nullable|integer|in:0,1', // 0: sudah, 1: belum
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi gagal',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validatedData = $validator->validated();

        try {
            $readiness_material = ReadinessMaterialRtnrt::create($validatedData);

            return response()->json([
                'success' => true,
                'message' => 'Readiness Material RT/NRT created successfully.',
                'data' => $readiness_material,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create Readiness Material RT/NRT.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $readiness_material = ReadinessMaterialRtnrt::find($id);

        if (!$readiness_material) {
            return response()->json([
                'success' => false,
                'message' => 'Readiness Material RT/NRT not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Readiness Material RT/NRT retrieved successfully.',
            'data' => $readiness_material,
        ], 200);
    }

    public function showByEvent(string $id)
    {
        $readiness_material = ReadinessMaterialRtnrt::orderBy('id', 'desc')->where('event_readiness_rtnrt_id', $id)->get();

        if (!$readiness_material) {
            return response()->json([
                'success' => false,
                'message' => 'Readiness Material RT/NRT not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Readiness Material RT/NRT retrieved successfully.',
            'data' => $readiness_material,
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $readiness_material = ReadinessMaterialRtnrt::find($id);

        if (!$readiness_material) {
            return response()->json([
                'success' => false,
                'message' => 'Readiness Material RT/NRT not found.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'material_name' => 'sometimes|string|max:100',
            'price_estimate' => 'sometimes|nullable|integer',
            'type' => 'sometimes|integer|in:0,1', // 0: LLDI, 1: Non LLDI
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
            $readiness_material->update($validatedData);

            return response()->json([
                'success' => true,
                'message' => 'Readiness Material RT/NRT updated successfully.',
                'data' => $readiness_material,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update Readiness Material RT/NRT.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $readiness_material = ReadinessMaterialRtnrt::find($id);

        if (!$readiness_material) {
            return response()->json([
                'success' => false,
                'message' => 'Readiness Material RT/NRT not found.',
            ], 404);
        }

        try {
            $readiness_material->delete();

            return response()->json([
                'success' => true,
                'message' => 'Readiness Material RT/NRT deleted successfully.',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete Readiness Material RT/NRT.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * additional function to create or update current status of readiness material RT/NRT
     */
    public function updateCurrentStatus(Request $request, string $id )
    {
        $readiness_material = ReadinessMaterialRtnrt::find($id);
        if (!$readiness_material) {
            return response()->json([
                'success' => false,
                'message' => 'Readiness Material RT/NRT not found.',
            ], 404);
        }
        $validator = Validator::make($request->all(), [
            'current_status' => 'sometimes|nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed for current status',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validatedData = $validator->validated();
        $readiness_material->update($validatedData);

        return response()->json([
            'success' => true,
            'message' => 'Current status updated successfully.',
            'data' => $readiness_material,
        ], 200);
    }

    /**
     * additional function to update status of readiness material RT/NRT
     */
    public function updateStatus(Request $request, string $id )
    {
        $readiness_material = ReadinessMaterialRtnrt::find($id);
        if (!$readiness_material) {
            return response()->json([
                'success' => false,
                'message' => 'Readiness Material RT/NRT not found.',
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
        $readiness_material->update($validatedData);

        return response()->json([
            'success' => true,
            'message' => 'Status updated successfully.',
            'data' => $readiness_material,
        ], 200);
    }
}
