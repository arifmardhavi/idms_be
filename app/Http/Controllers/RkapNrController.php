<?php

namespace App\Http\Controllers;

use App\Http\Resources\RkapNrCollection;
use App\Http\Resources\RkapNrResource;
use App\Models\DetailRkapNr;
use App\Models\RkapNr;
use App\Services\RkapNrService;
use Illuminate\Http\Request;

class RkapNrController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(RkapNrService $service)
    {
        $perPage = request()->get('per_page', 10); // default 10

        $search = request()->get('search');
        $sortBy = request()->get('sort_by', 'id'); // default sort by id
        $sortOrder = request()->get('sort_order', 'desc'); // default desc

        // whitelist kolom yang boleh di-sort
        $allowedSort = ['id', 'judul', 'created_at'];

        if (!in_array($sortBy, $allowedSort)) {
            $sortBy = 'id';
        }

        if (!in_array(strtolower($sortOrder), ['asc', 'desc'])) {
            $sortOrder = 'desc';
        }

        $query = RkapNr::with('detailRkapNr');

        // SEARCH
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('judul', 'like', "%{$search}%");
                // kalau mau tambah field lain:
                // ->orWhere('deskripsi', 'like', "%{$search}%");
            });
        }

        // SORT
        $query->orderBy($sortBy, $sortOrder);

        $data = $query->paginate($perPage);

        $summary = $service->getSummary();

        return (new RkapNrCollection($data))
            ->additional([
                'total' => $summary
            ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, RkapNrService $service)
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
            'data' => new RkapNrResource($rkap),
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id, RkapNrService $service)
    {
        $rkap = RkapNr::with('detailRkapNr')->find($id);

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
            'data' => new RkapNrResource($rkap),
            'total' => $summary,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id, RkapNrService $service)
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
        $rkap = RkapNr::find($id);

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
            'data' => new RkapNrResource($rkap),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $rkap = RkapNr::find($id);

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
        $rkap = RkapNr::find($id);

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
        $detail = DetailRkapNr::where('rkap_nr_id', $rkap->id)
            ->where('periode', $validated['periode'])
            ->first();

        // kalau periode belum ada
        if (!$detail) {

            // bikin baru
            $detail = DetailRkapNr::create([
                'rkap_nr_id' => $rkap->id,
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
