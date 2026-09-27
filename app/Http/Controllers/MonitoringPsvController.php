<?php

namespace App\Http\Controllers;

use App\Exports\MonitoringPsvExport;
use App\Http\Requests\UpdateMonitoringPsvRequest;
use App\Http\Resources\ApiResource;
use App\Http\Resources\MonitoringPsvResource;
use App\Models\MonitoringPsv;
use App\Services\MonitoringPsvDashboardService;
use App\Services\MonitoringPsvSyncService;
use App\Support\MasaBerlakuBucket;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class MonitoringPsvController extends Controller
{
    /**
     * Kolom tanggal COI yang dipakai untuk menghitung bucket masa berlaku.
     */
    private const DATE_COLUMN = 'cois.overdue_date';

    /**
     * =====================================================
     * GET /api/monitoring_psv
     * =====================================================
     */
    public function index(Request $request)
    {
        // Dikunci 1..100 mengikuti ActivityController. Tanpa ini FE (atau
        // klien iseng) bisa meminta per_page=999999 dan memuat seluruh tabel
        // sekaligus. Default 10 mengikuti MonitoringEquipmentController.
        $perPage = min(max($request->integer('per_page', 10), 1), 100);

        $data = $this->baseQuery($request)->paginate($perPage);

        return ApiResource::pagination(
            $data,
            MonitoringPsvResource::class
        );
    }

    /**
     * =====================================================
     * GET /api/monitoring_psv/{monitoring_psv}
     * =====================================================
     */
    public function show(MonitoringPsv $monitoringPsv)
    {
        $row = $this->reloadWithCoiData($monitoringPsv);

        $row->load(['coi.tag_number']);

        return new MonitoringPsvResource($row);
    }

    /**
     * =====================================================
     * PUT /api/monitoring_psv/{monitoring_psv}
     * =====================================================
     *
     * Hanya kolom inputan manual. Tag Number & Masa Berlaku read-only.
     */
    public function update(
        UpdateMonitoringPsvRequest $request,
        MonitoringPsv $monitoringPsv
    ) {
        try {

            $monitoringPsv->update($request->validated());

            return response()->json([

                'success' => true,

                'message' => 'Monitoring PSV updated successfully.',

                'data' => new MonitoringPsvResource(
                    $this->reloadWithCoiData($monitoringPsv)
                ),

            ]);

        } catch (\Throwable $e) {

            return response()->json([

                'success' => false,

                'message' => 'Failed to update Monitoring PSV.',

                'error' => config('app.debug')
                    ? $e->getMessage()
                    : null,

            ], 500);

        }
    }

    /**
     * =====================================================
     * POST /api/monitoring_psv/sync
     * =====================================================
     *
     * Backfill / repair: pastikan setiap COI PSV/TSV punya baris monitoring.
     * Idempotent, aman dipanggil berkali-kali.
     */
    public function sync(MonitoringPsvSyncService $service)
    {
        try {

            $result = $service->syncAll();

            return response()->json([

                'success' => true,

                'message' => 'Monitoring PSV synced from COI.',

                'data' => $result,

            ]);

        } catch (\Throwable $e) {

            return response()->json([

                'success' => false,

                'message' => 'Failed to sync Monitoring PSV.',

                'error' => config('app.debug')
                    ? $e->getMessage()
                    : null,

            ], 500);

        }
    }

    /**
     * =====================================================
     * GET /api/monitoring_psv/dashboard
     * =====================================================
     */
    public function dashboard(MonitoringPsvDashboardService $service)
    {
        try {

            return response()->json([

                'success' => true,

                'message' => 'Dashboard Monitoring PSV.',

                'data' => $service->getDashboard(),

            ]);

        } catch (\Throwable $e) {

            return response()->json([

                'success' => false,

                'message' => 'Failed load dashboard.',

                'error' => config('app.debug')
                    ? $e->getMessage()
                    : null,

            ], 500);

        }
    }

    /**
     * =====================================================
     * GET /api/monitoring_psv/export
     * =====================================================
     */
    public function export(Request $request)
    {
        activity()->log('export', 'MonitoringPsv');

        return Excel::download(

            new MonitoringPsvExport(
                $this->baseQuery($request)
            ),

            'Monitoring_PSV_'.now()->format('Ymd_His').'.xlsx'

        );
    }

    /**
     * =====================================================
     * QUERY DASAR
     * =====================================================
     *
     * Dipakai bersama oleh index() dan export() agar isi export selalu
     * sama dengan yang tampil di layar.
     */
    private function baseQuery(Request $request): Builder
    {
        $query = MonitoringPsv::query()
            ->psvTsv()
            ->withCoiData();

        /**
         * GLOBAL SEARCH
         */
        $search = $request->get('search');

        $query->when($search, function ($q) use ($search) {

            $q->where(function ($query) use ($search) {

                $query
                    ->where('tag_numbers.tag_number', 'like', "%{$search}%")
                    ->orWhere('monitoring_psv.pid_no', 'like', "%{$search}%")
                    ->orWhere('monitoring_psv.keterangan', 'like', "%{$search}%");

            });

        });

        /**
         * FILTER
         */
        $query->when(
            $request->filled('kategori'),
            fn ($q) => $q->where(
                'monitoring_psv.kategori',
                $request->kategori
            )
        );

        $query->when(
            $request->filled('status_redundant'),
            fn ($q) => $q->where(
                'monitoring_psv.status_redundant',
                $request->status_redundant
            )
        );

        MasaBerlakuBucket::filter(
            $query,
            self::DATE_COLUMN,
            $request->get('status_masa_berlaku')
        );

        /**
         * SORTING
         */
        $sortBy = $request->get('sort_by', 'sisa_hari');

        // Default "asc" = paling mendesak di atas. Nilai kosong dari FE
        // (mis. select yang belum dipilih) harus dianggap default, bukan desc.
        $sortOrder = strtolower(trim((string) $request->get('sort_order', 'asc'))) === 'desc'
            ? 'desc'
            : 'asc';

        $allowedSort = [

            'id' => 'monitoring_psv.id',

            'tag_number' => 'tag_numbers.tag_number',

            'masa_berlaku' => 'cois.overdue_date',

            'sisa_hari' => 'sisa_hari',

            'kategori' => 'monitoring_psv.kategori',

            'status_redundant' => 'monitoring_psv.status_redundant',

            'pid_no' => 'monitoring_psv.pid_no',

            'created_at' => 'monitoring_psv.created_at',

            'updated_at' => 'monitoring_psv.updated_at',

        ];

        if ($sortBy === 'status_masa_berlaku') {

            $query->orderByRaw(
                'FIELD('.MasaBerlakuBucket::sql(self::DATE_COLUMN)
                .", '".MasaBerlakuBucket::EXPIRED."'"
                .", '".MasaBerlakuBucket::WARNING."'"
                .", '".MasaBerlakuBucket::SAFE."') ".$sortOrder
            );

        } else {

            $query->orderBy(
                $allowedSort[$sortBy] ?? 'sisa_hari',
                $sortOrder
            );

        }

        return $query;
    }

    /**
     * Muat ulang satu baris beserta kolom dari tabel yang di-join.
     *
     * Diperlukan karena model hasil route-model binding belum punya
     * tag_number / overdue_date / sisa_hari — tanpa itu bucket masa berlaku
     * akan selalu salah terbaca sebagai "expired".
     *
     * Sengaja tanpa filter PSV/TSV supaya show() tetap bisa menjangkau baris
     * yang tag number-nya sudah berubah.
     */
    private function reloadWithCoiData(MonitoringPsv $monitoringPsv): MonitoringPsv
    {
        return MonitoringPsv::query()
            ->joinedToCoi()
            ->withCoiData()
            ->where('monitoring_psv.id', $monitoringPsv->getKey())
            ->firstOrFail();
    }
}
