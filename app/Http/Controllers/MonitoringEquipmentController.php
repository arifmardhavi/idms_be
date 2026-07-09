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
use Maatwebsite\Excel\Facades\Excel;


class MonitoringEquipmentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $perPage = $request->integer('per_page', 10);

        $search = $request->search;

        $sortBy = $request->get('sort_by', 'id');

        $sortOrder = $request->get('sort_order', 'desc');
        $currentPeriod = BusinessPeriod::current()['code'];
        $query = MonitoringEquipment::with([
            'tagNumber',
            'logs' => function ($query) use ($currentPeriod) {
            $query->where('period_code', '!=', $currentPeriod)
                  ->latest('period_code');
        }
        ]);

        /**
         * Search
         */
        $query->when($search, function ($q) use ($search) {

            $q->whereHas('tagNumber', function ($tag) use ($search) {

                $tag->where('tag_number', 'like', "%{$search}%");

            });

        });

        /**
         * Filter Status
         */
        $query->when(
            $request->filled('status'),
            fn($q) => $q->where(
                'status',
                $request->status
            )
        );

        /**
         * Sorting
         */

        $allowedSort = [

            'id',

            'status',

            'created_at',

            'updated_at'

        ];

        if (!in_array($sortBy, $allowedSort)) {

            $sortBy = 'id';

        }

        $query->orderBy($sortBy, $sortOrder);

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
}
