<?php

namespace App\Http\Controllers;

use App\Exports\MonitoringMemorandumExport;
use App\Http\Requests\StoreMonitoringMemorandumRequest;
use App\Http\Requests\UpdateMonitoringMemorandumRequest;
use App\Http\Resources\ApiResource;
use App\Http\Resources\MonitoringMemorandumResource;
use App\Models\MonitoringMemorandum;
use App\Services\MonitoringMemorandumDashboardService;
use App\Support\BacklogDays;
use App\Support\TagNumberIds;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class MonitoringMemorandumController extends Controller
{
    public function index(Request $request)
    {
        $perPage = min(max($request->integer('per_page', 10), 1), 100);

        return ApiResource::pagination(
            $this->baseQuery($request)->paginate($perPage),
            MonitoringMemorandumResource::class
        );
    }

    public function store(StoreMonitoringMemorandumRequest $request)
    {
        try {
            $row = DB::transaction(
                fn () => MonitoringMemorandum::create($request->validated())
            );

            return response()->json([
                'success' => true,
                'message' => 'Monitoring Memorandum created successfully.',
                'data' => new MonitoringMemorandumResource(
                    $row->load('historicalMemorandum')
                ),
            ], 201);
        } catch (\Throwable $e) {
            return $this->error('Failed to create Monitoring Memorandum.', $e);
        }
    }

    public function show(MonitoringMemorandum $monitoringMemorandum)
    {
        return new MonitoringMemorandumResource(
            $monitoringMemorandum->load('historicalMemorandum')
        );
    }

    public function update(
        UpdateMonitoringMemorandumRequest $request,
        MonitoringMemorandum $monitoringMemorandum
    ) {
        try {
            DB::transaction(function () use ($request, $monitoringMemorandum) {
                $monitoringMemorandum->update($request->validated());
                $monitoringMemorandum->refresh();
            });

            return response()->json([
                'success' => true,
                'message' => 'Monitoring Memorandum updated successfully.',
                'data' => new MonitoringMemorandumResource(
                    $monitoringMemorandum->load('historicalMemorandum')
                ),
            ]);
        } catch (\Throwable $e) {
            return $this->error('Failed to update Monitoring Memorandum.', $e);
        }
    }

    public function destroy(MonitoringMemorandum $monitoringMemorandum)
    {
        try {
            DB::transaction(fn () => $monitoringMemorandum->delete());

            return response()->json([
                'success' => true,
                'message' => 'Monitoring Memorandum deleted successfully.',
            ]);
        } catch (\Throwable $e) {
            return $this->error('Failed to delete Monitoring Memorandum.', $e);
        }
    }

    public function export(Request $request)
    {
        activity()->log('export', 'MonitoringMemorandum');

        return Excel::download(
            new MonitoringMemorandumExport($this->baseQuery($request)),
            'Monitoring_Memorandum_' . now()->format('Ymd_His') . '.xlsx'
        );
    }

    public function dashboard(MonitoringMemorandumDashboardService $service)
    {
        try {
            return response()->json([
                'success' => true,
                'message' => 'Dashboard Monitoring Memorandum.',
                'data' => $service->getDashboard(),
            ]);
        } catch (\Throwable $e) {
            return $this->error('Failed load dashboard.', $e);
        }
    }

    private function error(string $message, \Throwable $e)
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'error' => config('app.debug') ? $e->getMessage() : null,
        ], 500);
    }

    /**
     * Query dasar dipakai bersama index() dan export() agar isi export
     * selalu identik dengan hasil filter di layar.
     */
    private function baseQuery(Request $request): Builder
    {
        $query = MonitoringMemorandum::query()
            ->leftJoin(
                'historical_memorandum',
                'historical_memorandum.id',
                '=',
                'monitoring_memorandum.nota_dinas'
            )
            ->select('monitoring_memorandum.*')
            ->selectRaw(
                BacklogDays::daysSql('monitoring_memorandum.tanggal_issue')
                . ' as backlog_days'
            )
            ->with('historicalMemorandum');

        $search = $request->get('search');

        $query->when($search, function (Builder $query) use ($search) {
            $like = '%' . $search . '%';

            $query->where(function (Builder $query) use ($like) {
                $query
                    ->where('monitoring_memorandum.issue_operasional', 'like', $like)
                    ->orWhere('historical_memorandum.no_dokumen', 'like', $like)
                    ->orWhere('monitoring_memorandum.no_notif', 'like', $like)
                    ->orWhere('monitoring_memorandum.no_wo', 'like', $like)
                    ->orWhere('monitoring_memorandum.no_po', 'like', $like)
                    ->orWhere('monitoring_memorandum.keterangan', 'like', $like)
                    ->orWhereRaw(
                        TagNumberIds::tagSearchSql('monitoring_memorandum.tag_number_ids'),
                        [$like]
                    );
            });
        });

        foreach (['status', 'status_notif', 'status_wo', 'nota_dinas'] as $field) {
            $query->when(
                $request->filled($field),
                fn (Builder $q) => $q->where('monitoring_memorandum.' . $field, $request->get($field))
            );
        }

        TagNumberIds::applyFilter(
            $query,
            'monitoring_memorandum.tag_number_ids',
            $request->get('tag_number_ids')
        );

        $query->when(
            $request->filled('tanggal_issue_from'),
            fn (Builder $q) => $q->where(
                'monitoring_memorandum.tanggal_issue',
                '>=',
                $request->get('tanggal_issue_from')
            )
        );

        $query->when(
            $request->filled('tanggal_issue_to'),
            fn (Builder $q) => $q->where(
                'monitoring_memorandum.tanggal_issue',
                '<=',
                $request->get('tanggal_issue_to')
            )
        );

        $backlogColumn = BacklogDays::daysSql('monitoring_memorandum.tanggal_issue');

        $query->when(
            $request->filled('backlog_days_min'),
            fn (Builder $q) => $q->whereRaw($backlogColumn . ' >= ?', [(int) $request->get('backlog_days_min')])
        );

        $query->when(
            $request->filled('backlog_days_max'),
            fn (Builder $q) => $q->whereRaw($backlogColumn . ' <= ?', [(int) $request->get('backlog_days_max')])
        );

        $allowedSort = [
            'id' => 'monitoring_memorandum.id',
            'tanggal_issue' => 'monitoring_memorandum.tanggal_issue',
            'issue_operasional' => 'monitoring_memorandum.issue_operasional',
            'tag_number_ids' => 'monitoring_memorandum.tag_number_ids',
            'nota_dinas' => 'monitoring_memorandum.nota_dinas',
            'no_notif' => 'monitoring_memorandum.no_notif',
            'status_notif' => 'monitoring_memorandum.status_notif',
            'no_wo' => 'monitoring_memorandum.no_wo',
            'status_wo' => 'monitoring_memorandum.status_wo',
            'no_po' => 'monitoring_memorandum.no_po',
            'status' => 'monitoring_memorandum.status',
            'backlog_days' => 'backlog_days',
            'created_at' => 'monitoring_memorandum.created_at',
            'updated_at' => 'monitoring_memorandum.updated_at',
        ];

        $sortBy = $request->get('sort_by', 'tanggal_issue');
        $sortOrder = strtolower(trim((string) $request->get('sort_order', 'desc'))) === 'asc'
            ? 'asc'
            : 'desc';

        $query->orderBy(
            $allowedSort[$sortBy] ?? 'monitoring_memorandum.tanggal_issue',
            $sortOrder
        );

        return $query;
    }
}
