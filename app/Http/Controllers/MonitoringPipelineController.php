<?php

namespace App\Http\Controllers;

use App\Exports\MonitoringPipelineExport;
use App\Exports\MonitoringPipelineTemplateExport;
use App\Http\Requests\ImportMonitoringPipelineRequest;
use App\Http\Requests\StoreMonitoringPipelineRequest;
use App\Http\Requests\UpdateMonitoringPipelineRequest;
use App\Http\Resources\ApiResource;
use App\Http\Resources\MonitoringPipelineResource;
use App\Models\MonitoringPipeline;
use App\Services\MonitoringPipelineImportService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class MonitoringPipelineController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $perPage = $request->integer('per_page', 10);

        $query = $this->baseQuery();

        $this->applyFilters($query, $request);

        $this->applySort($query, $request);

        $data = $query->paginate($perPage);

        return ApiResource::pagination(
            $data,
            MonitoringPipelineResource::class
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreMonitoringPipelineRequest $request)
    {
        try {

            $validated = $request->validated();

            $monitoringPipeline = DB::transaction(function () use ($validated) {

                return MonitoringPipeline::create($validated);
            });

            return response()->json([
                'success' => true,
                'message' => 'Monitoring Pipeline created successfully.',
                'data' => new MonitoringPipelineResource(
                    $monitoringPipeline->load('tagNumber')
                ),
            ], 201);

        } catch (\Throwable $e) {

            return response()->json([
                'success' => false,
                'message' => 'Failed to create Monitoring Pipeline.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);

        }
    }

    /**
     * Display the specified resource.
     */
    public function show(MonitoringPipeline $monitoringPipeline)
    {
        $monitoringPipeline->load('tagNumber');

        return new MonitoringPipelineResource($monitoringPipeline);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(
        UpdateMonitoringPipelineRequest $request,
        MonitoringPipeline $monitoringPipeline
    ) {
        try {

            $validated = $request->validated();

            DB::transaction(function () use (&$monitoringPipeline, $validated) {

                $monitoringPipeline->update($validated);

                $monitoringPipeline->refresh();
            });

            return response()->json([
                'success' => true,
                'message' => 'Monitoring Pipeline updated successfully.',
                'data' => new MonitoringPipelineResource(
                    $monitoringPipeline->load('tagNumber')
                ),
            ]);

        } catch (\Throwable $e) {

            return response()->json([
                'success' => false,
                'message' => 'Failed to update Monitoring Pipeline.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);

        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(MonitoringPipeline $monitoringPipeline)
    {
        try {

            DB::transaction(function () use ($monitoringPipeline) {

                $monitoringPipeline->delete();
            });

            return response()->json([
                'success' => true,
                'message' => 'Monitoring Pipeline deleted successfully.',
            ]);

        } catch (\Throwable $e) {

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete Monitoring Pipeline.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);

        }
    }

    public function import(
        ImportMonitoringPipelineRequest $request,
        MonitoringPipelineImportService $service
    ) {
        $result = $service->import(
            $request->file('file')
        );

        activity()->log('import', 'MonitoringPipeline', [
            'object' => ($result['summary']['success'] ?? 0).' data',
        ]);

        return $result;
    }

    /**
     * Download Excel Template
     */
    public function downloadTemplate()
    {
        return Excel::download(

            new MonitoringPipelineTemplateExport,

            'Template_Monitoring_Pipeline.xlsx'

        );
    }

    /**
     * Export Monitoring Pipeline Data
     */
    public function export(Request $request)
    {
        activity()->log('export', 'MonitoringPipeline');

        $query = $this->baseQuery();

        $this->applyFilters($query, $request);

        $this->applySort($query, $request);

        $rows = $query->get()
            ->map(fn ($item, $index) => array_merge(
                ['no' => $index + 1],
                $item->only(array_keys($this->exportColumns())),
                ['tag_number' => optional($item->tagNumber)->tag_number]
            ))
            ->map(fn ($row) => $this->formatExportRow($row))
            ->toArray();

        return Excel::download(

            new MonitoringPipelineExport($rows, $this->exportColumns()),

            'Monitoring_Pipeline_'.now()->format('Ymd_His').'.xlsx'

        );
    }

    /**
     * Query dasar: join tag_numbers + relasi tagNumber.
     * Dipakai bersama index() dan export().
     */
    private function baseQuery(): Builder
    {
        return MonitoringPipeline::query()

            ->leftJoin(
                'tag_numbers',
                'tag_numbers.id',
                '=',
                'monitoring_pipeline.tag_number_id'
            )

            ->select('monitoring_pipeline.*')

            ->with('tagNumber');
    }

    private function applyFilters(Builder $query, Request $request): void
    {
        $search = $request->get('search');

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
                        'monitoring_pipeline.tml_drawing_number',
                        'like',
                        "%{$search}%"
                    )

                    ->orWhere(
                        'monitoring_pipeline.grade_material',
                        'like',
                        "%{$search}%"
                    )

                    ->orWhere(
                        'monitoring_pipeline.sch',
                        'like',
                        "%{$search}%"
                    )

                    ->orWhere(
                        'monitoring_pipeline.pipe_condition',
                        'like',
                        "%{$search}%"
                    )

                    ->orWhere(
                        'monitoring_pipeline.maintenance_status',
                        'like',
                        "%{$search}%"
                    )

                    ->orWhere(
                        'monitoring_pipeline.rencana_tindak_lanjut',
                        'like',
                        "%{$search}%"
                    );

            });

        });

        /**
         * =====================================================
         * FILTER (partial match)
         * =====================================================
         */

        $likeFilters = [
            'insulation' => 'monitoring_pipeline.insulation',
            'grade_material' => 'monitoring_pipeline.grade_material',
            'pipe_condition' => 'monitoring_pipeline.pipe_condition',
            'maintenance_status' => 'monitoring_pipeline.maintenance_status',
        ];

        foreach ($likeFilters as $param => $column) {
            $query->when($request->filled($param), function ($q) use ($request, $param, $column) {
                $value = trim((string) $request->get($param));

                if ($value === '') {
                    return;
                }

                $q->where($column, 'like', '%' . $value . '%');
            });
        }

        /**
         * =====================================================
         * FILTER (equality)
         * =====================================================
         */

        $equalityFilters = [
            'api_service_class' => 'monitoring_pipeline.api_service_class',
            'unit_id' => 'tag_numbers.unit_id',
        ];

        foreach ($equalityFilters as $param => $column) {
            $query->when(
                $request->filled($param),
                fn ($q) => $q->where($column, $request->get($param))
            );
        }

        /**
         * =====================================================
         * FILTER (rentang tanggal, inclusive)
         * =====================================================
         */

        $dateRanges = [
            'tanggal_pasang_line' => 'monitoring_pipeline.tanggal_pasang_line',
            'tanggal_ut_terakhir' => 'monitoring_pipeline.tanggal_ut_terakhir',
            'next_plan_inspection_api' => 'monitoring_pipeline.next_plan_inspection_api',
        ];

        foreach ($dateRanges as $param => $column) {
            $query->when(
                $request->filled($param.'_from'),
                fn ($q) => $q->where(
                    $column,
                    '>=',
                    $request->get($param.'_from')
                )
            );

            $query->when(
                $request->filled($param.'_to'),
                fn ($q) => $q->where(
                    $column,
                    '<=',
                    $request->get($param.'_to')
                )
            );
        }
    }

    private function applySort(Builder $query, Request $request): void
    {
        $sortBy = $request->get('sort_by', 'created_at');

        $sortOrder = strtolower($request->get('sort_order', 'desc')) === 'asc'
            ? 'asc'
            : 'desc';

        $allowedSort = [

            'id' => 'monitoring_pipeline.id',

            'tag_number' => 'tag_numbers.tag_number',

            'tml_drawing_number' => 'monitoring_pipeline.tml_drawing_number',

            'api_service_class' => 'monitoring_pipeline.api_service_class',

            'insulation' => 'monitoring_pipeline.insulation',

            'grade_material' => 'monitoring_pipeline.grade_material',

            'nps' => 'monitoring_pipeline.nps',

            'od' => 'monitoring_pipeline.od',

            'sch' => 'monitoring_pipeline.sch',

            't_nom' => 'monitoring_pipeline.t_nom',

            'operating_press' => 'monitoring_pipeline.operating_press',

            'design_press' => 'monitoring_pipeline.design_press',

            'design_temp' => 'monitoring_pipeline.design_temp',

            's' => 'monitoring_pipeline.s',

            'e' => 'monitoring_pipeline.e',

            'w' => 'monitoring_pipeline.w',

            'y' => 'monitoring_pipeline.y',

            'min_req_thk' => 'monitoring_pipeline.min_req_thk',

            'half_nom_thk' => 'monitoring_pipeline.half_nom_thk',

            'tanggal_pasang_line' => 'monitoring_pipeline.tanggal_pasang_line',

            'actual_thk' => 'monitoring_pipeline.actual_thk',

            'tanggal_ut_terakhir' => 'monitoring_pipeline.tanggal_ut_terakhir',

            'corrosion_rate' => 'monitoring_pipeline.corrosion_rate',

            'remaining_life' => 'monitoring_pipeline.remaining_life',

            'alert_structural_thickness' => 'monitoring_pipeline.alert_structural_thickness',

            'next_plan_inspection_api' => 'monitoring_pipeline.next_plan_inspection_api',

            'used_plan_inspection' => 'monitoring_pipeline.used_plan_inspection',

            'pipe_condition' => 'monitoring_pipeline.pipe_condition',

            'estimated_end_of_life' => 'monitoring_pipeline.estimated_end_of_life',

            'maintenance_status' => 'monitoring_pipeline.maintenance_status',

            'rencana_tindak_lanjut' => 'monitoring_pipeline.rencana_tindak_lanjut',

            'created_at' => 'monitoring_pipeline.created_at',

            'updated_at' => 'monitoring_pipeline.updated_at',

        ];

        $query->orderBy(
            $allowedSort[$sortBy] ?? 'monitoring_pipeline.created_at',
            $sortOrder
        );
    }

    /**
     * Kolom export: key => label heading. 'no' direset manual.
     */
    private function exportColumns(): array
    {
        return [

            'no' => 'No',

            'tag_number' => 'Tag Number',

            'tml_drawing_number' => 'TML Drawing Number',

            'api_service_class' => 'API 570 Service Class',

            'insulation' => 'Insulation OR Non-Insulation',

            'grade_material' => 'Grade Material',

            'nps' => 'NPS',

            'od' => 'OD (mm)',

            'sch' => 'SCH',

            't_nom' => 'T. NOM (mm)',

            'operating_press' => 'Operating Press (kg/cm2)',

            'design_press' => 'Design Press (kg/cm2)',

            'design_temp' => 'Design Temp (kg/cm2)',

            's' => 'S (MPa)',

            'e' => 'E',

            'w' => 'W',

            'y' => 'Y',

            'min_req_thk' => 'Min Req Thk (mm)',

            'half_nom_thk' => '50% Nom. Thk (mm)',

            'tanggal_pasang_line' => 'Tanggal Pasang Line',

            'actual_thk' => 'Actual THK (mm)',

            'tanggal_ut_terakhir' => 'Tanggal UT Terakhir',

            'corrosion_rate' => 'Corrosion Rate',

            'remaining_life' => 'Remaining Life',

            'alert_structural_thickness' => 'Alert Structural Thickness (API 574)',

            'next_plan_inspection_api' => 'Next Plan Inspection API 570',

            'used_plan_inspection' => 'Used Plan Inspection',

            'pipe_condition' => 'Pipe Condition (API 574)',

            'estimated_end_of_life' => 'Estimated End Of Life (EOL)',

            'maintenance_status' => 'Maintenance Status',

            'rencana_tindak_lanjut' => 'Rencana Tindak Lanjut',

        ];
    }

    /**
     * Format nilai row untuk export: tanggal => Y-m-d, float berlebih dibulatkan.
     */
    private function formatExportRow(array $row): array
    {
        $dateKeys = [
            'tanggal_pasang_line',
            'tanggal_ut_terakhir',
            'next_plan_inspection_api',
            'used_plan_inspection',
            'estimated_end_of_life',
        ];

        foreach ($dateKeys as $key) {
            $value = $row[$key] ?? null;

            $row[$key] = $value === null
                ? null
                : \Illuminate\Support\Carbon::parse($value)->format('Y-m-d');
        }

        $floatKeys = [
            'nps', 'od', 't_nom', 'operating_press', 'design_press',
            'design_temp', 's', 'e', 'w', 'y', 'min_req_thk',
            'half_nom_thk', 'actual_thk', 'corrosion_rate', 'remaining_life',
            'alert_structural_thickness',
        ];

        foreach ($floatKeys as $key) {
            $value = $row[$key] ?? null;

            if ($value !== null) {
                $row[$key] = round((float) $value, 4);
            }
        }

        return $row;
    }
}
