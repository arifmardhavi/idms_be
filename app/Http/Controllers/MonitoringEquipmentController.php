<?php

namespace App\Http\Controllers;

use App\Http\Resources\MonitoringEquipmentResource;
use App\Models\MonitoringEquipment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MonitoringEquipmentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $monitoringEquipment = MonitoringEquipment::with('tagNumber')
            ->whereHas('tagNumber', function ($query) {
                $query->where('status', 1);
            })
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Monitoring Equipment retrieved successfully.',
            'data' => MonitoringEquipmentResource::collection($monitoringEquipment),
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'tag_number_id' => 'required|exists:tag_numbers,id|unique:monitoring_equipments,tag_number_id',
            'criticality' => 'required|string',
            'sece' => 'required|in:0,1', // 0: tidak ada, 1: ada
            'status' => 'required|in:0,1,2,3', // 0: hijau, 1: biru, 2: kuning, 3: merah
            'tindak_lanjut' => 'required|string',
            'target' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'data' => $validator->errors(),
            ], 422);
        }

        $validatedData = $validator->validated();

        try {
            $monitoringEquipment = MonitoringEquipment::create($validatedData);
            return response()->json([
                'success' => true,
                'message' => 'Monitoring Equipment created successfully.',
                'data' => new MonitoringEquipmentResource($monitoringEquipment),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create Monitoring Equipment.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $monitoringEquipment = MonitoringEquipment::find($id);
        if (!$monitoringEquipment) {
            return response()->json([
                'success' => false,
                'message' => 'Monitoring Equipment not found.',
            ], 404);
        }
        return response()->json([
            'success' => true,
            'message' => 'Monitoring Equipment retrieved successfully.',
            'data' => new MonitoringEquipmentResource($monitoringEquipment),
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $monitoringEquipment = MonitoringEquipment::find($id);
        if (!$monitoringEquipment) {
            return response()->json([
                'success' => false,
                'message' => 'Monitoring Equipment not found.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'criticality' => 'required|string',
            'sece' => 'required|in:0,1',
            'status' => 'required|in:0,1,2,3',
            'tindak_lanjut' => 'required|string',
            'target' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'data' => $validator->errors(),
            ], 422);
        }

        $validatedData = $validator->validated();

        try {
            $monitoringEquipment->update($validatedData);
            return response()->json([
                'success' => true,
                'message' => 'Monitoring Equipment updated successfully.',
                'data' => new MonitoringEquipmentResource($monitoringEquipment),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update Monitoring Equipment.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $monitoringEquipment = MonitoringEquipment::find($id);
        if (!$monitoringEquipment) {
            return response()->json([
                'success' => false,
                'message' => 'Monitoring Equipment not found.',
            ], 404);
        }

        try {
            $monitoringEquipment->delete();
            return response()->json([
                'success' => true,
                'message' => 'Monitoring Equipment deleted successfully.',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete Monitoring Equipment.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
