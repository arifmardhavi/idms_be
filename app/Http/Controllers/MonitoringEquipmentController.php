<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMonitoringEquipmentRequest;
use App\Http\Requests\UpdateMonitoringEquipmentRequest;
use App\Http\Resources\MonitoringEquipmentResource;
use App\Models\MonitoringEquipment;
use App\Models\MonitoringEquipmentLog;
use App\Helpers\BusinessPeriod;
use App\Http\Resources\ApiResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\ImportMonitoringEquipmentRequest;
use App\Services\MonitoringEquipmentImportService;
use App\Exports\MonitoringEquipmentTemplateExport;
use App\Exports\MonitoringEquipmentExport;
use App\Exports\MonitoringEquipmentLogExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Services\MonitoringEquipmentDashboardService;


class MonitoringEquipmentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $perPage = $request->integer('per_page', 10);

        $search = $request->get('search');

        $sortBy = $request->get('sort_by', 'id');

        $sortOrder = strtolower($request->get('sort_order', 'desc')) == 'asc'
            ? 'asc'
            : 'desc';

        $currentPeriod = BusinessPeriod::current()['code'];

        $query = MonitoringEquipment::query()

            ->leftJoin(
                'tag_numbers',
                'tag_numbers.id',
                '=',
                'monitoring_equipment.tag_number_id'
            )

            ->select('monitoring_equipment.*')

            ->with([
                'tagNumber',
                'logs' => function ($query) use ($currentPeriod) {

                    $query->where(
                        'period_code',
                        '!=',
                        $currentPeriod
                    )->latest('period_code');

                }

            ]);

        /**
         * =====================================================
         * GLOBAL SEARCH
         * =====================================================
         */
        $query->when($search, function ($q) use ($search) {

            $q->where(function ($query) use ($search) {

                $query

                    ->where(
                        'tag_numbers.tag_number',
                        'like',
                        "%{$search}%"
                    )

                    ->orWhere(
                        'monitoring_equipment.jenis_kerusakan',
                        'like',
                        "%{$search}%"
                    )

                    ->orWhere(
                        'monitoring_equipment.penyebab',
                        'like',
                        "%{$search}%"
                    )

                    ->orWhere(
                        'monitoring_equipment.penanganan_sementara',
                        'like',
                        "%{$search}%"
                    )

                    ->orWhere(
                        'monitoring_equipment.perbaikan_permanen',
                        'like',
                        "%{$search}%"
                    )

                    ->orWhere(
                        'monitoring_equipment.progress_perbaikan_permanen',
                        'like',
                        "%{$search}%"
                    )

                    ->orWhere(
                        'monitoring_equipment.kendala_perbaikan',
                        'like',
                        "%{$search}%"
                    )

                    ->orWhere(
                        'monitoring_equipment.estimasi_perbaikan',
                        'like',
                        "%{$search}%"
                    )

                    ->orWhere(
                        'monitoring_equipment.target',
                        'like',
                        "%{$search}%"
                    );

            });

        });

        /**
         * =====================================================
         * FILTER
         * =====================================================
         */

        /**
         * Status
         */
        $query->when(
            $request->filled('status'),
            fn ($q) => $q->where(
                'monitoring_equipment.status',
                $request->status
            )
        );

        /**
         * Criticality
         */
        $query->when(
            $request->filled('criticality'),
            fn ($q) => $q->where(
                'tag_numbers.criticality',
                $request->criticality
            )
        );

        /**
         * SECE
         */
        $query->when(
            $request->filled('sece'),
            fn ($q) => $q->where(
                'tag_numbers.sece',
                $request->sece
            )
        );

        /**
         * =====================================================
         * SORTING
         * =====================================================
         */

        $allowedSort = [

            'id' => 'monitoring_equipment.id',

            'tag_number' => 'tag_numbers.tag_number',

            'criticality' => 'tag_numbers.criticality',

            'sece' => 'tag_numbers.sece',

            'status' => 'monitoring_equipment.status',

            'jenis_kerusakan' => 'monitoring_equipment.jenis_kerusakan',

            'penyebab' => 'monitoring_equipment.penyebab',

            'penanganan_sementara' => 'monitoring_equipment.penanganan_sementara',

            'perbaikan_permanen' => 'monitoring_equipment.perbaikan_permanen',

            'progress_perbaikan_permanen' => 'monitoring_equipment.progress_perbaikan_permanen',

            'kendala_perbaikan' => 'monitoring_equipment.kendala_perbaikan',

            'estimasi_perbaikan' => 'monitoring_equipment.estimasi_perbaikan',

            'target' => 'monitoring_equipment.target',

            'created_at' => 'monitoring_equipment.created_at',

            'updated_at' => 'monitoring_equipment.updated_at',

        ];

        $query->orderBy(
            $allowedSort[$sortBy] ?? 'monitoring_equipment.id',
            $sortOrder
        );

        $data = $query->paginate($perPage);

        return ApiResource::pagination(
            $data,
            MonitoringEquipmentResource::class
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreMonitoringEquipmentRequest $request)
    {
        try {

            $validated = $request->validated();

            $monitoringEquipment = DB::transaction(function () use ($validated) {

                $monitoringEquipment = MonitoringEquipment::create($validated);

                $period = BusinessPeriod::current();

                MonitoringEquipmentLog::create([

                    ...collect($monitoringEquipment->getAttributes())
                        ->only((new MonitoringEquipmentLog())->getFillable())
                        ->toArray(),

                    'period_code' => $period['code'],
                    'period_start' => $period['start'],
                    'period_end' => $period['end'],

                ]);

                return $monitoringEquipment;
            });

            return response()->json([
                'success' => true,
                'message' => 'Monitoring Equipment created successfully.',
                'data' => new MonitoringEquipmentResource(
                    $monitoringEquipment->load('tagNumber')
                )
            ], 201);

        } catch (\Throwable $e) {

            return response()->json([
                'success' => false,
                'message' => 'Failed to create Monitoring Equipment.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);

        }
    }

    /**
     * Display the specified resource.
     */
    public function show(MonitoringEquipment $monitoringEquipment)
    {
        $currentPeriod = BusinessPeriod::current()['code'];

        $monitoringEquipment->load([
            'tagNumber',
            'logs' => function ($query) use ($currentPeriod) {
                $query->where('period_code', '!=', $currentPeriod)
                    ->latest('period_code');
            }
        ]);

        return new MonitoringEquipmentResource($monitoringEquipment);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateMonitoringEquipmentRequest $request,MonitoringEquipment $monitoringEquipment)
    {
        $validated = $request->validated();

        try {

            DB::transaction(function () use (&$monitoringEquipment,$validated) 
            {

                /**
                 * Update Monitoring Equipment
                 */
                $monitoringEquipment->update($validated);

                /**
                 * Reload Latest Data
                 */
                $monitoringEquipment->refresh();

                /**
                 * Current Business Period
                 */
                $period = BusinessPeriod::current();

                /**
                 * Snapshot Data
                 */
                $snapshot = array_merge(

                    collect($monitoringEquipment->getAttributes())
                        ->only((new MonitoringEquipmentLog())->getFillable())
                        ->toArray(),

                    [

                        'period_code' => $period['code'],

                        'period_start' => $period['start'],

                        'period_end' => $period['end'],

                    ]

                );

                /**
                 * Create / Update Snapshot
                 */
                MonitoringEquipmentLog::updateOrCreate(

                    [

                        'tag_number_id' => $monitoringEquipment->tag_number_id,

                        'period_code' => $period['code'],

                    ],

                    $snapshot

                );

                /**
                 * Cleanup
                 * Keep only last 3 periods
                 */
                MonitoringEquipmentLog::where(
                    'tag_number_id',
                    $monitoringEquipment->tag_number_id
                )
                    ->whereNotIn(
                        'period_code',
                        BusinessPeriod::allowedPeriods()
                    )
                    ->delete();

            });

            return response()->json([

                'success' => true,

                'message' => 'Monitoring Equipment updated successfully.',

                'data' => new MonitoringEquipmentResource(

                    $monitoringEquipment
                        ->load([
                            'tagNumber',
                            'logs'
                        ])

                )

            ]);

        } catch (\Throwable $e) {

            return response()->json([

                'success' => false,

                'message' => 'Failed to update Monitoring Equipment.',

                'error' => config('app.debug')
                    ? $e->getMessage()
                    : null

            ], 500);

        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(MonitoringEquipment $monitoringEquipment)
    {
        try {

            DB::transaction(function () use ($monitoringEquipment) {

                MonitoringEquipmentLog::where(
                    'tag_number_id',
                    $monitoringEquipment->tag_number_id
                )->delete();

                $monitoringEquipment->delete();

            });

            return response()->json([

                'success' => true,

                'message' => 'Monitoring Equipment deleted successfully.'

            ]);

        } catch (\Throwable $e) {

            return response()->json([

                'success' => false,

                'message' => 'Failed to delete Monitoring Equipment.',

                'error' => config('app.debug')
                    ? $e->getMessage()
                    : null

            ], 500);

        }
    }

    public function import(
    ImportMonitoringEquipmentRequest $request, MonitoringEquipmentImportService $service)
    {
        return $service->import(
            $request->file('file')
        );
    }

    /**
     * Download Excel Template
     */
    public function downloadTemplate()
    {
        return Excel::download(

            new MonitoringEquipmentTemplateExport(),

            'Monitoring_Equipment_Template.xlsx'

        );
    }


    /**
     * Export Monitoring Equipment Data
     */
    public function export(Request $request)
    {
        return Excel::download(

            new MonitoringEquipmentExport(

                $request->only([
                    'search',
                    'criticality',
                    'status',
                    'sece'
                ])

            ),

            'Monitoring_Equipment_' . now()->format('Ymd_His') . '.xlsx'

        );
    }

    /**
     * Export Monitoring Equipment Logs Data
     */
    public function exportLogs(Request $request)
    {
        return Excel::download(

            new MonitoringEquipmentLogExport(

                $request->only([

                    'search',

                    'period_code',

                    'criticality',

                    'status'

                ])

            ),

            'Monitoring_Equipment_Logs_' .
            now()->format('Ymd_His') .
            '.xlsx'

        );
    }

    public function dashboard(MonitoringEquipmentDashboardService $service)
    {
        try {

            return response()->json([

                'success' => true,

                'message' => 'Dashboard Monitoring Equipment.',

                'data' => $service->getDashboard()

            ]);

        } catch (\Throwable $e) {

            return response()->json([

                'success' => false,

                'message' => 'Failed load dashboard.',

                'error' => config('app.debug')
                    ? $e->getMessage()
                    : null

            ],500);

        }
    }
}
