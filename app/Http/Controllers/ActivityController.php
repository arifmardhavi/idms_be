<?php

namespace App\Http\Controllers;

use App\Models\LogActivity;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ActivityController extends Controller
{
    protected array $actionLabels = [
        'login'    => 'Login',
        'logout'   => 'Logout',
        'visit'    => 'Mengunjungi Fitur',
        'view'     => 'Melihat File',
        'download' => 'Mengunduh File',
        'export'   => 'Export Data',
        'create'   => 'Menambah Data',
        'update'   => 'Mengubah Data',
        'delete'   => 'Menghapus Data',
        'import'   => 'Import Data',
    ];

    /**
     * Daftar log aktivitas (filter + pagination).
     * GET /activity?user_id=&action=&module=&from=&to=&per_page=
     */
    public function index(Request $request)
    {
        $query = LogActivity::with('user');

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->integer('user_id'));
        }
        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }
        if ($request->filled('module')) {
            $query->where('module', $request->module);
        }
        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->to);
        }

        $perPage = $request->integer('per_page', 20);
        $logs = $query->orderByDesc('created_at')->paginate(min(max($perPage, 1), 100));

        $data = $logs->map(fn($log) => $this->serialize($log))->all();

        return response()->json([
            'success' => true,
            'message' => 'Log aktivitas ditemukan.',
            'data'    => [
                'current_page' => $logs->currentPage(),
                'per_page'     => $logs->perPage(),
                'total'        => $logs->total(),
                'last_page'    => $logs->lastPage(),
                'items'        => $data,
            ],
        ]);
    }

    /**
     * Mencatat kunjungan fitur. Hanya direkam jika duration_seconds >= 5.
     * POST /activity/visit {feature, duration_seconds}
     */
    public function visit(Request $request)
    {
        $request->validate([
            'feature'           => 'required|string|max:100',
            'duration_seconds'  => 'required|numeric|min:0',
        ]);

        if ($request->duration_seconds < 5) {
            return response()->json([
                'success' => true,
                'message' => 'Kunjungan terlalu singkat, tidak direkam.',
                'data'    => null,
            ]);
        }

        $log = activity()->log('visit', $request->feature, [
            'metadata' => ['duration_seconds' => (float) $request->duration_seconds],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Kunjungan fitur direkam.',
            'data'    => $log ? $this->serialize($log) : null,
        ], 201);
    }

    /**
     * Top-10 Most Active User.
     * Skor: view + download + visit + export + login.
     * GET /activity/ranking/active
     */
    public function rankingActive(Request $request)
    {
        $actions = ['view', 'download', 'visit', 'export', 'login'];
        return $this->ranking($actions, $request);
    }

    /**
     * Top-10 Most Contributor.
     * Skor: create + update + delete + import.
     * GET /activity/ranking/contributor
     */
    public function rankingContributor(Request $request)
    {
        $actions = ['create', 'update', 'delete', 'import'];
        return $this->ranking($actions, $request);
    }

    protected function ranking(array $actions, Request $request)
    {
        $query = LogActivity::whereIn('action', $actions);

        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->to);
        }

        $rows = $query->select('user_id', DB::raw('COUNT(*) as score'))
            ->whereNotNull('user_id')
            ->groupBy('user_id')
            ->orderByDesc('score')
            ->limit(10)
            ->get();

        $names = $this->resolveNames($rows->pluck('user_id')->all());

        $data = $rows->values()->map(function ($row, $i) use ($names) {
            return [
                'rank'    => $i + 1,
                'user_id' => $row->user_id,
                'name'    => $names[$row->user_id] ?? 'User#' . $row->user_id,
                'score'   => (int) $row->score,
            ];
        })->all();

        return response()->json([
            'success' => true,
            'message' => 'Ranking berhasil.',
            'data'    => $data,
        ]);
    }

    /**
     * Ringkasan aktivitas (KPI).
     * GET /activity/stats/overview
     */
    public function statsOverview(Request $request)
    {
        $base = $this->applyDateFilter(LogActivity::query(), $request);

        return response()->json([
            'success' => true,
            'data'    => [
                'total_logs'         => $base->count(),
                'total_users_active' => (clone $base)->whereNotNull('user_id')->distinct('user_id')->count('user_id'),
                'today_logs'         => LogActivity::whereDate('created_at', today())->count(),
                'today_logins'       => LogActivity::whereDate('created_at', today())
                                            ->where('action', 'login')->count(),
                'top_action'         => (clone $base)->groupBy('action')
                                            ->orderByRaw('COUNT(*) DESC')
                                            ->pluck('action')
                                            ->first(),
            ],
        ]);
    }

    /**
     * Tren aktivitas per hari/bulan.
     * GET /activity/stats/trend?group=day|month
     */
    public function statsTrend(Request $request)
    {
        $group = $request->input('group', 'day');
        $format = $group === 'month' ? '%Y-%m' : '%Y-%m-%d';
        $label = $group === 'month' ? 'month' : 'day';

        $query = $this->applyDateFilter(LogActivity::query(), $request);
        $rows = $query->select(DB::raw("DATE_FORMAT(created_at, '{$format}') as period"), DB::raw('COUNT(*) as count'))
            ->groupBy('period')
            ->orderBy('period')
            ->get();

        $data = $rows->map(fn($r) => [
            'date'  => $r->period,
            'count' => (int) $r->count,
        ])->all();

        return response()->json([
            'success' => true,
            'data'    => ['group' => $label, 'series' => $data],
        ]);
    }

    /**
     * Distribusi per aksi. GET /activity/stats/by-action
     */
    public function statsByAction(Request $request)
    {
        $query = $this->applyDateFilter(LogActivity::query(), $request);
        $rows = $query->select('action', DB::raw('COUNT(*) as count'))
            ->groupBy('action')
            ->orderByDesc('count')
            ->get();

        $data = $rows->map(fn($r) => [
            'action'       => $r->action,
            'action_label' => $this->actionLabels[$r->action] ?? $r->action,
            'count'        => (int) $r->count,
        ])->all();

        return response()->json(['success' => true, 'data' => $data]);
    }

    /**
     * Distribusi per modul. GET /activity/stats/by-module
     */
    public function statsByModule(Request $request)
    {
        $query = $this->applyDateFilter(LogActivity::query(), $request);
        $rows = $query->select('module', DB::raw('COUNT(*) as count'))
            ->groupBy('module')
            ->orderByDesc('count')
            ->get();

        $data = $rows->map(fn($r) => [
            'module' => $r->module,
            'count'  => (int) $r->count,
        ])->all();

        return response()->json(['success' => true, 'data' => $data]);
    }

    /**
     * Aktivitas per user (total + breakdown per aksi). GET /activity/stats/by-user
     */
    public function statsByUser(Request $request)
    {
        $query = $this->applyDateFilter(LogActivity::query()->whereNotNull('user_id'), $request);

        $rows = $query->select('user_id', 'action', DB::raw('COUNT(*) as count'))
            ->groupBy('user_id', 'action')
            ->get();

        $byUser = [];
        foreach ($rows as $r) {
            $byUser[$r->user_id][$r->action] = (int) $r->count;
        }

        $names = $this->resolveNames(array_keys($byUser));

        $data = collect($byUser)
            ->map(function ($breakdown, $userId) use ($names) {
                return [
                    'user_id'   => $userId,
                    'name'      => $names[$userId] ?? 'User#' . $userId,
                    'total'     => array_sum($breakdown),
                    'breakdown' => $breakdown,
                ];
            })
            ->sortByDesc('total')
            ->values()
            ->all();

        return response()->json(['success' => true, 'data' => $data]);
    }

    protected function applyDateFilter($query, Request $request)
    {
        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->to);
        }
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->integer('user_id'));
        }
        if ($request->filled('module')) {
            $query->where('module', $request->module);
        }
        return $query;
    }

    protected function resolveNames(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }
        return User::whereIn('id', $ids)->pluck('fullname', 'id')->all();
    }

    protected function serialize(LogActivity $log): array
    {
        return [
            'id'            => $log->id,
            'user'          => $log->user ? ['id' => $log->user_id, 'fullname' => $log->user->fullname] : null,
            'action'        => $log->action,
            'action_label'  => $this->actionLabels[$log->action] ?? $log->action,
            'module'        => $log->module,
            'module_label'  => $log->module,
            'record_id'     => $log->record_id,
            'record_label'  => $log->record_label,
            'description'   => $log->description,
            'metadata'      => $log->metadata,
            'ip_address'    => $log->ip_address,
            'time'          => $log->created_at?->format('Y-m-d H:i:s'),
            'time_ago'      => $log->created_at?->diffForHumans(),
        ];
    }
}
