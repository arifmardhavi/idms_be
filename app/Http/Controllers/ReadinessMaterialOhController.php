<?php

namespace App\Http\Controllers;

use App\Models\ReadinessMaterialOh;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ReadinessMaterialOhController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $readiness_material = ReadinessMaterialOh::orderBy('id', 'desc')->get();
        return response()->json([
            'success' => true,
            'message' => 'Readiness Material OH retrieved successfully.',
            'data' => $readiness_material,
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'event_readiness_oh_id' => 'required|exists:event_readiness_ohs,id',
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
            $readiness_material = ReadinessMaterialOh::create($validatedData);

            return response()->json([
                'success' => true,
                'message' => 'Readiness Material OH created successfully.',
                'data' => $readiness_material,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create Readiness Material OH.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $readiness_material = ReadinessMaterialOh::find($id);

        if (!$readiness_material) {
            return response()->json([
                'success' => false,
                'message' => 'Readiness Material OH not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Readiness Material OH retrieved successfully.',
            'data' => $readiness_material,
        ], 200);
    }

    public function showByEvent(string $id)
    {
        $readiness_material = ReadinessMaterialOh::orderBy('id', 'desc')->where('event_readiness_oh_id', $id)->get();

        if (!$readiness_material) {
            return response()->json([
                'success' => false,
                'message' => 'Readiness Material OH not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Readiness Material OH retrieved successfully.',
            'data' => $readiness_material,
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $readiness_material = ReadinessMaterialOh::find($id);

        if (!$readiness_material) {
            return response()->json([
                'success' => false,
                'message' => 'Readiness Material OH not found.',
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
                'message' => 'Readiness Material OH updated successfully.',
                'data' => $readiness_material,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update Readiness Material OH.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $readiness_material = ReadinessMaterialOh::find($id);

        if (!$readiness_material) {
            return response()->json([
                'success' => false,
                'message' => 'Readiness Material OH not found.',
            ], 404);
        }

        try {
            $readiness_material->delete();

            return response()->json([
                'success' => true,
                'message' => 'Readiness Material OH deleted successfully.',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete Readiness Material OH.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * additional function to create or update current status of readiness material OH
     */
    public function updateCurrentStatus(Request $request, string $id )
    {
        $readiness_material = ReadinessMaterialOh::find($id);
        if (!$readiness_material) {
            return response()->json([
                'success' => false,
                'message' => 'Readiness Material OH not found.',
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
     * additional function to update status of readiness material OH
     */
    public function updateStatus(Request $request, string $id )
    {
        $readiness_material = ReadinessMaterialOh::find($id);
        if (!$readiness_material) {
            return response()->json([
                'success' => false,
                'message' => 'Readiness Material OH not found.',
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

    /**
     * Display dashboard summary for the specified event readiness.
     */

    public function dashboard(string $id)
    {
        try {
            $materials = ReadinessMaterialOh::with(
                'rekomendasi_material_oh',
                'notif_material_oh',
                'job_plan_material_oh',
                'pr_material_oh',
                'tender_material_oh',
                'po_material_oh',
                'fabrikasi_material_oh',
                'delivery_material_oh'
            )->orderBy('id', 'desc')->where('event_readiness_oh_id', $id)->get();

            $steps = [
                'rekomendasi_material_oh',
                'notif_material_oh',
                'job_plan_material_oh',
                'pr_material_oh',
                'tender_material_oh',
                'po_material_oh',
                'fabrikasi_material_oh',
                'delivery_material_oh',
            ];

            // Hitung jumlah data di setiap step (hanya step terakhir)
            $stepCounts = array_fill_keys($steps, 0);

            foreach ($materials as $material) {
                $lastStep = null;
                foreach ($steps as $step) {
                    if ($material->$step) {
                        $lastStep = $step;
                    }
                }

                if ($lastStep) {
                    $stepCounts[$lastStep]++;
                }
            }

            // Hitung jumlah berdasarkan type
            $typeCounts = [
                'lldi' => $materials->where('type', 0)->count(),
                'non_lldi' => $materials->where('type', 1)->count(),
            ];

            // Hitung rata-rata total progress keseluruhan
            $totalProgressValues = $materials->map(function ($item) {
                return (float) str_replace('%', '', $item->total_progress);
            });

            $averageProgress = $totalProgressValues->count() > 0
                ? number_format($totalProgressValues->avg(), 2) . '%'
                : '0.00%';


            // Tambahkan total data keseluruhan
            $totalData = $materials->count();

            return response()->json([
                'success' => true,
                'message' => 'Dashboard Readiness Material OH summary retrieved successfully.',
                'data' => [
                    'steps' => $stepCounts,
                    'types' => $typeCounts,
                    'average_total_progress' => $averageProgress,
                    'total_data' => $totalData,
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load dashboard data.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }
}
