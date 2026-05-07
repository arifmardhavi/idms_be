<?php

namespace App\Http\Controllers;

use App\Http\Resources\RkapRtCollection;
use App\Http\Resources\RkapRtResource;
use App\Models\DetailRkapRt;
use App\Models\RkapRt;
use App\Services\RkapRtService;
use Illuminate\Http\Request;

class RkapRtController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(RkapRtService $service)
    {
        $perPage = request()->get('per_page', 10); // default 10
        $data = RkapRt::with('detailRkapRt')->orderBy('id', 'desc')->paginate($perPage);

        $summary = $service->getSummary();

        return (new RkapRtCollection($data))
            ->additional([
                'total' => $summary
            ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, RkapRtService $service)
    {
        // VALIDASI DASAR
        $validated = $request->validate([
            'judul' => 'required|string|max:255',
            'data_periode' => 'required|array',
            'data_periode.*.periode' => 'required|integer|min:1|max:12',
            'data_periode.*.plan' => 'nullable|integer|min:0',
            'data_periode.*.actual' => 'nullable|integer|min:0',
        ]);

        // VALIDASI DUPLIKAT PERIODE
        $periodeList = collect($validated['data_periode'])->pluck('periode');

        if ($periodeList->duplicates()->isNotEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Periode tidak boleh duplikat.',
            ], 422);
        }

        // CALL SERVICE
        $rkap = $service->store($validated);

        // RESPONSE
        return response()->json([
            'success' => true,
            'message' => 'RKAP created successfully.',
            'data' => new RkapRtResource($rkap),
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id, RkapRtService $service)
    {
        $rkap = RkapRt::with('detailRkapRt')->find($id);

        if (!$rkap) {
            return response()->json([
                'success' => false,
                'message' => 'RKAP not found.',
            ], 404);
        }

        // 🔥 summary khusus 1 RKAP (bukan semua data)
        $summary = $service->getSummaryByRkap($rkap->id);

        return response()->json([
            'success' => true,
            'message' => 'RKAP retrieved successfully.',
            'data' => new RkapRtResource($rkap),
            'total' => $summary,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id, RkapRtService $service)
    {
        // VALIDASI
        $validated = $request->validate([
            'judul' => 'required|string|max:255',
            'data_periode' => 'required|array',
            'data_periode.*.periode' => 'required|integer|min:1|max:12',
            'data_periode.*.plan' => 'nullable|integer|min:0',
            'data_periode.*.actual' => 'nullable|integer|min:0',
        ]);

        // VALIDASI DUPLIKAT
        $periodeList = collect($validated['data_periode'])->pluck('periode');

        if ($periodeList->duplicates()->isNotEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Periode tidak boleh duplikat.',
            ], 422);
        }

        // CEK DATA
        $rkap = RkapRt::find($id);

        if (!$rkap) {
            return response()->json([
                'success' => false,
                'message' => 'RKAP not found.',
            ], 404);
        }

        // CALL SERVICE
        $rkap = $service->update($rkap, $validated);

        return response()->json([
            'success' => true,
            'message' => 'RKAP updated successfully.',
            'data' => new RkapRtResource($rkap),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $rkap = RkapRt::find($id);

        if (!$rkap) {
            return response()->json([
                'success' => false,
                'message' => 'RKAP not found.',
            ], 404);
        }

        $rkap->delete();

        return response()->json([
            'success' => true,
            'message' => 'RKAP deleted successfully.',
        ]);
    }

    /**
     * Additional method to update 'actual' value for a specific period without affecting other data.
     */

    public function updateActual(Request $request, string $id)
    {
        $rkap = RkapRt::find($id);

        if (!$rkap) {
            return response()->json([
                'success' => false,
                'message' => 'RKAP not found.',
            ], 404);
        }

        // VALIDASI
        $validated = $request->validate([
            'periode' => 'required|integer|min:1|max:12',
            'actual' => 'nullable|integer|min:0',
        ]);

        // CARI DETAIL
        $detail = DetailRkapRt::where('rkap_rt_id', $rkap->id)
            ->where('periode', $validated['periode'])
            ->first();

        // kalau periode belum ada
        if (!$detail) {

            // bikin baru
            $detail = DetailRkapRt::create([
                'rkap_rt_id' => $rkap->id,
                'periode' => $validated['periode'],
                'plan' => 0,
                'actual' => $validated['actual'],
            ]);

        } else {

            // update existing
            $detail->update([
                'actual' => $validated['actual'],
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Actual updated successfully.',
            'data' => [
                'id' => $rkap->id,
                'periode' => $detail->periode,
                'actual' => (int) $detail->actual,
            ]
        ]);
    }
}
