<?php

namespace App\Http\Controllers;

use App\Models\ReadinessJasa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ReadinessJasaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $readiness_jasa = ReadinessJasa::with(
            'rekomendasi_jasa',
            'rekomendasi_jasa.historical_memorandum',
            'notif_jasa',
            'job_plan_jasa',
            'pr_jasa',
            'tender_jasa',
            'contract_jasa',
        )->orderBy('id', 'desc')->get();
        return response()->json([
            'success' => true,
            'message' => 'Readiness TA Jasa retrieved successfully.',
            'data' => $readiness_jasa,
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'event_readiness_id' => 'required|exists:event_readinesses,id',
            'jasa_name' => 'required|string|max:100',
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
            $readiness_jasa = ReadinessJasa::create($validatedData);

            return response()->json([
                'success' => true,
                'message' => 'Readiness TA jasa created successfully.',
                'data' => $readiness_jasa,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create Readiness TA jasa.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $readiness_jasa = ReadinessJasa::find($id);

        if (!$readiness_jasa) {
            return response()->json([
                'success' => false,
                'message' => 'Readiness TA jasa not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Readiness TA jasa retrieved successfully.',
            'data' => $readiness_jasa,
        ], 200);
    }

    public function showByEvent(string $id)
    {
        $readiness_jasa = ReadinessJasa::with([
            'rekomendasi_jasa',
            'rekomendasi_jasa.historical_memorandum',
            'notif_jasa',
            'job_plan_jasa',
            'pr_jasa',
            'tender_jasa',
            'contract_jasa',
            'contract_jasa.contract',
        ])->where('event_readiness_id', $id)->get();

        if ($readiness_jasa->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Readiness TA jasa not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Readiness TA jasa retrieved successfully.',
            'data' => $readiness_jasa,
        ], 200);
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $readiness_jasa = ReadinessJasa::find($id);

        if (!$readiness_jasa) {
            return response()->json([
                'success' => false,
                'message' => 'Readiness TA jasa not found.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'jasa_name' => 'sometimes|string|max:100',
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
                'message' => 'Readiness TA jasa updated successfully.',
                'data' => $readiness_jasa,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update Readiness TA jasa.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $readiness_jasa = ReadinessJasa::find($id);

        if (!$readiness_jasa) {
            return response()->json([
                'success' => false,
                'message' => 'Readiness TA jasa not found.',
            ], 404);
        }

        try {
            $readiness_jasa->delete();

            return response()->json([
                'success' => true,
                'message' => 'Readiness TA jasa deleted successfully.',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete Readiness TA jasa.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }

    public function dashboard(string $id)
    {
        try {
            $readiness_jasa = ReadinessJasa::with([
                'rekomendasi_jasa',
                'notif_jasa',
                'job_plan_jasa',
                'pr_jasa',
                'tender_jasa',
                'contract_jasa',
            ])->where('event_readiness_id', $id)->get();

            $steps = [
                'rekomendasi_jasa',
                'notif_jasa',
                'job_plan_jasa',
                'pr_jasa',
                'tender_jasa',
                'contract_jasa',
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
                'message' => 'Dashboard Readiness TA Jasa summary retrieved successfully.',
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
