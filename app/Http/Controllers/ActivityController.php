<?php

namespace App\Http\Controllers;

use App\Models\LogActivity;
use App\Models\User;
use App\Support\ActivityModule;
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
     * Daftar log aktivitas (search + sort + date filter + pagination).
     * GET /activity?page=&per_page=&search=&from=&to=&sort_by=&sort_order=
     * Search & sort berlaku pada: action, action_label, module, module_label, description.
     */
    public function index(Request $request)
    {
        $query = LogActivity::with('user');

        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->to);
        }

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('action', 'like', "%{$search}%")
                    ->orWhere('module', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");

                $actions = collect($this->actionLabels)
                    ->filter(fn($label) => stripos($label, $search) !== false)
                    ->keys()
                    ->all();

                if ($actions) {
                    $q->orWhereIn('action', $actions);
                }
            });
        }

        $sortBy = $request->input('sort_by');
        $sortOrder = strtolower($request->input('sort_order', 'desc')) === 'asc' ? 'asc' : 'desc';

        if ($sortBy === 'action_label') {
            $case = 'CASE action';
            foreach ($this->actionLabels as $a => $label) {
                $case .= " WHEN '{$a}' THEN '{$label}'";
            }
            $case .= ' ELSE action END';
            $query->orderByRaw("{$case} {$sortOrder}")->orderByDesc('created_at');
        } elseif ($sortBy === 'module_label') {
            $query->orderBy('module', $sortOrder)->orderByDesc('created_at');
        } elseif (in_array($sortBy, ['action', 'module', 'description'], true)) {
            $query->orderBy($sortBy, $sortOrder)->orderByDesc('created_at');
        } else {
            $query->orderByDesc('created_at');
        }

        $perPage = $request->integer('per_page', 20);
        $logs = $query->paginate(min(max($perPage, 1), 100));

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
     * Daftar label fitur utama (canonical) yang boleh dipakai FE.
     * FE mengirim label ini persis sebagai `feature` pada POST /activity/visit.
     * GET /activity/features
     */
    public function features()
    {
        $features = config('log-activity.features', []);
        $data = collect($features)->map(fn($label, $path) => [
            'path'  => $path,
            'label' => $label,
        ])->values()->all();

        return response()->json([
            'success' => true,
            'message' => 'Daftar fitur utama ditemukan.',
            'data'    => $data,
        ]);
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
            ->get();

        $merged = [];
        foreach ($rows as $r) {
            $label = ActivityModule::label($r->module);
            $merged[$label] = ($merged[$label] ?? 0) + (int) $r->count;
        }

        $data = collect($merged)
            ->map(fn($count, $label) => [
                'module'       => $label,
                'module_label' => $label,
                'count'        => $count,
            ])
            ->sortByDesc('count')
            ->values()
            ->all();

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
            'module_label'  => ActivityModule::label($log->module),
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
