<?php

namespace App\Http\Controllers;

use App\Models\EventReadiness;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class EventReadinessController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $event_readinesses = EventReadiness::orderBy('id', 'desc')->get();

        return response()->json([
            'success' => true,
            'message' => 'Event Readiness retrieved successfully.',
            'data' => $event_readinesses,
        ], 200);

    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'event_name' => 'required|string|unique:event_readinesses,event_name',
            'tanggal_ta' => 'required|date',
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
            $event_readiness = EventReadiness::create($validatedData);

            return response()->json([
                'success' => true,
                'message' => 'Event Readiness created successfully.',
                'data' => $event_readiness,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Event Readiness creation failed.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $event_readiness = EventReadiness::find($id);
        if (!$event_readiness) {
            return response()->json([
                'success' => false,
                'message' => 'Event Readiness not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Event Readiness retrieved successfully.',
            'data' => $event_readiness,
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $event_readiness = EventReadiness::find($id);
        if (!$event_readiness) {
            return response()->json([
                'success' => false,
                'message' => 'Event Readiness not found.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'event_name' => 'required|string|unique:event_readinesses,event_name,' . $id,
            'tanggal_ta' => 'nullable|date',
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
                'message' => 'Event Readiness updated successfully.',
                'data' => $event_readiness,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Event Readiness update failed.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $event_readiness = EventReadiness::find($id);
        if (!$event_readiness) {
            return response()->json([
                'success' => false,
                'message' => 'Event Readiness not found.',
            ], 404);
        }

        try {
            $event_readiness->delete();

            return response()->json([
                'success' => true,
                'message' => 'Event Readiness deleted successfully.',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Event Readiness deletion failed.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * additional function to update status
     */
    public function updateStatus(Request $request, string $id)
    {
        $event_readiness = EventReadiness::find($id);
        if (!$event_readiness) {
            return response()->json([
                'success' => false,
                'message' => 'Event Readiness not found.',
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
                'message' => 'Status Event Readiness updated successfully.',
                'data' => $event_readiness,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Status Event Readiness update failed.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
