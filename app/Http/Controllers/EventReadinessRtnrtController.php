<?php

namespace App\Http\Controllers;

use App\Models\EventReadinessRtnrt;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;

class EventReadinessRtnrtController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $event_readinesses = EventReadinessRtnrt::orderBy('id', 'desc')->get();

        return response()->json([
            'success' => true,
            'message' => 'Event Readiness RT/NRT retrieved successfully.',
            'data' => $event_readinesses,
        ], 200);

    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'event_name' => 'required|string|unique:event_readiness_rtnrts,event_name',
            'status' => 'required|integer|in:0,1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi gagal',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validatedData = $validator->validated();

        try {
            $event_readiness = EventReadinessRtnrt::create($validatedData);

            return response()->json([
                'success' => true,
                'message' => 'Event Readiness RT/NRT created successfully.',
                'data' => $event_readiness,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Event Readiness RT/NRT creation failed.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $event_readiness = EventReadinessRtnrt::find($id);
        if (!$event_readiness) {
            return response()->json([
                'success' => false,
                'message' => 'Event Readiness RT/NRT not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Event Readiness RT/NRT retrieved successfully.',
            'data' => $event_readiness,
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $event_readiness = EventReadinessRtnrt::find($id);
        if (!$event_readiness) {
            return response()->json([
                'success' => false,
                'message' => 'Event Readiness RT/NRT not found.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'event_name' => 'required|string|unique:event_readiness_rtnrts,event_name,' . $id,
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
            $event_readiness->update($validatedData);

            return response()->json([
                'success' => true,
                'message' => 'Event Readiness RT/NRT updated successfully.',
                'data' => $event_readiness,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Event Readiness RT/NRT update failed.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $event_readiness = EventReadinessRtnrt::find($id);
        if (!$event_readiness) {
            return response()->json([
                'success' => false,
                'message' => 'Event Readiness RT/NRT not found.',
            ], 404);
        }

        try {
            $event_readiness->delete();

            return response()->json([
                'success' => true,
                'message' => 'Event Readiness RT/NRT deleted successfully.',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Event Readiness RT/NRT deletion failed.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * additional function to update status
     */
    public function updateStatus(Request $request, string $id)
    {
        $event_readiness = EventReadinessRtnrt::find($id);
        if (!$event_readiness) {
            return response()->json([
                'success' => false,
                'message' => 'Event Readiness RT/NRT not found.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
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
            $event_readiness->update($validatedData);

            return response()->json([
                'success' => true,
                'message' => 'Status Event Readiness RT/NRT updated successfully.',
                'data' => $event_readiness,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Status Event Readiness RT/NRT update failed.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
