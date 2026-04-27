<?php

namespace App\Http\Controllers;

use App\Models\ReadinessJasaRtnrt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ReadinessJasaRtnrtController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $readiness_jasa = ReadinessJasaRtnrt::with('rekomendasi_jasa_rtnrt.historical_memorandum')->orderBy('id', 'desc')->get();
        return response()->json([
            'success' => true,
            'message' => 'Readiness Jasa RT/NRT retrieved successfully.',
            'data' => $readiness_jasa,
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'event_readiness_rtnrt_id' => 'required|exists:event_readiness_rtnrts,id',
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
            $readiness_jasa = ReadinessJasaRtnrt::create($validatedData);

            return response()->json([
                'success' => true,
                'message' => 'Readiness Jasa RT/NRT created successfully.',
                'data' => $readiness_jasa,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create Readiness Jasa RT/NRT.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $readiness_jasa = ReadinessJasaRtnrt::find($id);

        if (!$readiness_jasa) {
            return response()->json([
                'success' => false,
                'message' => 'Readiness Jasa RT/NRT not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Readiness Jasa RT/NRT retrieved successfully.',
            'data' => $readiness_jasa,
        ], 200);
    }

    public function showByEvent(string $id)
    {
        $readiness_jasa = ReadinessJasaRtnrt::with('rekomendasi_jasa_rtnrt.historical_memorandum')->where('event_readiness_rtnrt_id', $id)->get();

        if ($readiness_jasa->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Readiness Jasa RT/NRT not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Readiness Jasa RT/NRT retrieved successfully.',
            'data' => $readiness_jasa,
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $readiness_jasa = ReadinessJasaRtnrt::find($id);

        if (!$readiness_jasa) {
            return response()->json([
                'success' => false,
                'message' => 'Readiness Jasa RT/NRT not found.',
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
                'message' => 'Readiness Jasa RT/NRT updated successfully.',
                'data' => $readiness_jasa,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update Readiness Jasa RT/NRT.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $readiness_jasa = ReadinessJasaRtnrt::find($id);

        if (!$readiness_jasa) {
            return response()->json([
                'success' => false,
                'message' => 'Readiness Jasa RT/NRT not found.',
            ], 404);
        }

        try {
            $readiness_jasa->delete();

            return response()->json([
                'success' => true,
                'message' => 'Readiness Jasa RT/NRT deleted successfully.',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete Readiness Jasa RT/NRT.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * additional function to create or update current status of readiness jasa
     */
    public function updateCurrentStatus(Request $request, string $id )
    {
        $readiness_jasa = ReadinessJasaRtnrt::find($id);
        if (!$readiness_jasa) {
            return response()->json([
                'success' => false,
                'message' => 'Readiness Jasa RT/NRT not found.',
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
        $readiness_jasa->update($validatedData);

        return response()->json([
            'success' => true,
            'message' => 'Current status updated successfully.',
            'data' => $readiness_jasa,
        ], 200);
    }

    public function updateStatus(Request $request, string $id )
    {
        $readiness_jasa = ReadinessJasaRtnrt::find($id);
        if (!$readiness_jasa) {
            return response()->json([
                'success' => false,
                'message' => 'Readiness Jasa RT/NRT not found.',
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

    public function dashboard(string $id)
    {
        try {
            $readiness_jasa = ReadinessJasaRtnrt::with([
                'rekomendasi_jasa_rtnrt',
                'notif_jasa_rtnrt',
                'job_plan_jasa_rtnrt',
                'pr_jasa_rtnrt',
                'tender_jasa_rtnrt',
                'contract_jasa_rtnrt',
            ])->where('event_readiness_rtnrt_id', $id)->get();

            $steps = [
                'rekomendasi_jasa_rtnrt',
                'notif_jasa_rtnrt',
                'job_plan_jasa_rtnrt',
                'pr_jasa_rtnrt',
                'tender_jasa_rtnrt',
                'contract_jasa_rtnrt',
            ];

            // Hitung jumlah data di setiap step (hanya step terakhir)
            $stepCounts = array_fill_keys($steps, 0);

            foreach ($readiness_jasa as $jasa) {
                $lastStep = null;
                foreach ($steps as $step) {
                    if ($jasa->$step) {
                        $lastStep = $step;
                    }
                }

                if ($lastStep) {
                    $stepCounts[$lastStep]++;
                }
            }

            // Hitung rata-rata total progress keseluruhan
            $totalProgressValues = $readiness_jasa->map(function ($item) {
                return (float) str_replace('%', '', $item->total_progress);
            });

            $averageProgress = $totalProgressValues->count() > 0
                ? number_format($totalProgressValues->avg(), 2) . '%'
                : '0.00%';

            // Tambahkan total data keseluruhan
            $totalData = $readiness_jasa->count();

            return response()->json([
                'success' => true,
                'message' => 'Dashboard Readiness Jasa RT/NRT summary retrieved successfully.',
                'data' => [
                    'steps' => $stepCounts,
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
