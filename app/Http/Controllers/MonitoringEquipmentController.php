<?php

namespace App\Http\Controllers;

use App\Http\Resources\MonitoringEquipmentResource;
use App\Models\MonitoringEquipment;
use App\Models\MonitoringEquipmentLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class MonitoringEquipmentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $monitoringEquipment = MonitoringEquipment::with([
            'tagNumber',
            'logs'
        ])
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
            'tag_number_id' => 'required|exists:tag_numbers,id|unique:monitoring_equipment,tag_number_id',
            'criticality' => 'required|in:0,1,2,3,4', // 0: High, 1: Medium High, 2: Secondary Medium, 3: Negligible, 4: Low
            'sece' => 'required|in:0,1', // 0: tidak, 1: ya
            'status' => 'required|in:0,1,2,3', // 0: High, 1: Medium, 2: Low, 3: Breakdown
            'jenis_kerusakan' => 'required|string',
            'penyebab' => 'required|string',
            'penanganan_sementara' => 'required|string',
            'perbaikan_permanen' => 'required|string',
            'progress_perbaikan_permanen' => 'required|string',
            'kendala_perbaikan' => 'required|string',
            'estimasi_perbaikan' => 'required|integer',
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
                'data' => new MonitoringEquipmentResource(
                    $monitoringEquipment->fresh()->load([
                        'tagNumber',
                        'logs'
                    ])
                ),
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
        $monitoringEquipment = MonitoringEquipment::with([
            'tagNumber',
            'logs'
        ])->find($id);
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
            'criticality' => 'required|in:0,1,2,3,4', // 0: High, 1: Medium High, 2: Secondary Medium, 3: Negligible, 4: Low
            'sece' => 'required|in:0,1', // 0: tidak, 1: ya
            'status' => 'required|in:0,1,2,3', // 0: High, 1: Medium, 2: Low, 3: Breakdown
            'jenis_kerusakan' => 'required|string',
            'penyebab' => 'required|string',
            'penanganan_sementara' => 'required|string',
            'perbaikan_permanen' => 'required|string',
            'progress_perbaikan_permanen' => 'required|string',
            'kendala_perbaikan' => 'required|string',
            'estimasi_perbaikan' => 'required|integer',
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
                'data' => new MonitoringEquipmentResource(
                    $monitoringEquipment->fresh()->load([
                        'tagNumber',
                        'logs'
                    ])
                ),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update Monitoring Equipment.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function updateLog(Request $request, string $id)
    {
        $monitoringEquipment = MonitoringEquipment::find($id);
        if (!$monitoringEquipment) {
            return response()->json([
                'success' => false,
                'message' => 'Monitoring Equipment not found.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'criticality' => 'required|in:0,1,2,3,4', // 0: High, 1: Medium High, 2: Secondary Medium, 3: Negligible, 4: Low
            'sece' => 'required|in:0,1', // 0: tidak, 1: ya
            'status' => 'required|in:0,1,2,3', // 0: High, 1: Medium, 2: Low, 3: Breakdown
            'jenis_kerusakan' => 'required|string',
            'penyebab' => 'required|string',
            'penanganan_sementara' => 'required|string',
            'perbaikan_permanen' => 'required|string',
            'progress_perbaikan_permanen' => 'required|string',
            'kendala_perbaikan' => 'required|string',
            'estimasi_perbaikan' => 'required|integer',
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

            DB::transaction(function () use ($monitoringEquipment, $validatedData) {

                MonitoringEquipmentLog::create(
                    collect($monitoringEquipment->getAttributes())
                        ->only((new MonitoringEquipmentLog())->getFillable())
                        ->toArray()
                );

                $monitoringEquipment->update($validatedData);

            });

            return response()->json([
                'success' => true,
                'message' => 'Monitoring Equipment updated successfully and log created.',
                'data' => new MonitoringEquipmentResource(
                    $monitoringEquipment->fresh()->load([
                        'tagNumber',
                        'logs'
                    ])
                ),
            ]);

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
