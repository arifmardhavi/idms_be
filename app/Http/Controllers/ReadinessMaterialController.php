<?php

namespace App\Http\Controllers;

use App\Models\ReadinessMaterial;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ReadinessMaterialController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $readiness_material = ReadinessMaterial::with(
            'rekomendasi_material',
            'rekomendasi_material.historical_memorandum',
            'notif_material',
            'job_plan_material',
            'pr_material',
            'tender_material',
            'po_material',
            'fabrikasi_material',
            'delivery_material'
        )->orderBy('id', 'desc')->get();
        return response()->json([
            'success' => true,
            'message' => 'Readiness TA Material retrieved successfully.',
            'data' => $readiness_material,
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'event_readiness_id' => 'required|exists:event_readinesses,id',
            'material_name' => 'required|string|max:100',
            'type' => 'nullable|integer|in:0,1', // 0: LLDI, 1: Non LLDI
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
            $readiness_material = ReadinessMaterial::create($validatedData);

            return response()->json([
                'success' => true,
                'message' => 'Readiness TA Material created successfully.',
                'data' => $readiness_material,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create Readiness TA Material.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $readiness_material = ReadinessMaterial::find($id);

        if (!$readiness_material) {
            return response()->json([
                'success' => false,
                'message' => 'Readiness TA Material not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Readiness TA Material retrieved successfully.',
            'data' => $readiness_material,
        ], 200);
    }

    public function showByEvent(string $id)
    {
        $readiness_material = ReadinessMaterial::with(
            'rekomendasi_material',
            'rekomendasi_material.historical_memorandum',
            'notif_material',
            'job_plan_material',
            'pr_material',
            'tender_material',
            'po_material',
            'po_material.contract',
            'fabrikasi_material',
            'delivery_material'
        )->orderBy('id', 'desc')->where('event_readiness_id', $id)->get();

        if (!$readiness_material) {
            return response()->json([
                'success' => false,
                'message' => 'Readiness TA Material not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Readiness TA Material retrieved successfully.',
            'data' => $readiness_material,
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $readiness_material = ReadinessMaterial::find($id);

        if (!$readiness_material) {
            return response()->json([
                'success' => false,
                'message' => 'Readiness TA Material not found.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'material_name' => 'sometimes|string|max:100',
            'type' => 'sometimes|integer|in:0,1', // 0: LLDI, 1: Non LLDI
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
                'message' => 'Readiness TA Material updated successfully.',
                'data' => $readiness_material,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update Readiness TA Material.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $readiness_material = ReadinessMaterial::find($id);

        if (!$readiness_material) {
            return response()->json([
                'success' => false,
                'message' => 'Readiness TA Material not found.',
            ], 404);
        }

        try {
            $readiness_material->delete();

            return response()->json([
                'success' => true,
                'message' => 'Readiness TA Material deleted successfully.',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete Readiness TA Material.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display dashboard summary for the specified event readiness.
     */

    public function dashboard(string $id)
    {
        try {
            $materials = ReadinessMaterial::with(
                'rekomendasi_material',
                'notif_material',
                'job_plan_material',
                'pr_material',
                'tender_material',
                'po_material',
                'fabrikasi_material',
                'delivery_material'
            )->orderBy('id', 'desc')->where('event_readiness_id', $id)->get();

            $steps = [
                'rekomendasi_material',
                'notif_material',
                'job_plan_material',
                'pr_material',
                'tender_material',
                'po_material',
                'fabrikasi_material',
                'delivery_material',
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
                'message' => 'Dashboard Readiness TA Material summary retrieved successfully.',
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
