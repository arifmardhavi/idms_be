<?php

namespace App\Http\Controllers;

use App\Models\LogActivity;
use Illuminate\Http\Request;

class LogActivityController extends Controller
{
    public function index()
    {
        $logs = LogActivity::with('user')
            ->latest()
            ->get();

        $formatted = $logs->map(function ($log) {
            return [
                'id'         => $log->id,
                'user'       => $log->user?->fullname ?? 'System',
                'module'     => $log->module,
                'action'     => $log->action,
                'changes'    => $log->changes,
                'ip_address' => $log->ip_address,
                'user_agent' => $log->user_agent,
                'time'       => $log->created_at->diffForHumans()
            ];
        });

        return response()->json([
            'data' => $formatted
        ]);
    }


    public function showByUser($user_id)
    {
        $logs = LogActivity::with('user')
            ->where('user_id', $user_id)
            ->orderBy('created_at', 'desc')
            ->get();

        // Hitung count per module
        $countPerModule = $logs
            ->groupBy('module')
            ->map(fn ($items) => $items->count());

        // Format + hilangkan duplikat berdasarkan module + action
        $formatted = $logs
            ->unique(fn ($log) => $log->module . '|' . $log->action)
            ->values()
            ->map(function ($log) use ($countPerModule) {
                return [
                    'id'              => $log->id,
                    'user'            => $log->user?->fullname ?? 'System',
                    'module'          => $log->module,
                    'action'          => $log->action,
                    'changes'         => $log->changes,
                    'ip_address'      => $log->ip_address,
                    'user_agent'      => $log->user_agent,
                    'time'            => $log->created_at->diffForHumans(),
                    'count_in_module' => $countPerModule[$log->module] ?? 0
                ];
            });

        return response()->json([
            'data' => $formatted
        ]);
    }

    public function showByAllUser()
    {
        $logs = LogActivity::with('user')
            ->orderBy('created_at', 'desc')
            ->get();

        // Group by user_id
        $grouped = $logs->groupBy('user_id')->map(function ($userLogs) {
            $userName = $userLogs->first()->user?->fullname ?? 'System';

            // Group lagi berdasarkan module
            $modules = $userLogs->groupBy('module')->map(function ($moduleLogs) {
                
                // Hitung count tiap action di module ini
                $actions = $moduleLogs->groupBy('action')->map(function ($actionLogs) {
                    return [
                        'count' => $actionLogs->count(),
                        'logs'  => $actionLogs->map(function ($log) {
                            return [
                                'id'         => $log->id,
                                'action'     => $log->action,
                                'changes'    => $log->changes,
                                'ip_address' => $log->ip_address,
                                'user_agent' => $log->user_agent,
                                'time'       => $log->created_at->diffForHumans()
                            ];
                        })->values()
                    ];
                });

                return [
                    'total_in_module' => $moduleLogs->count(),
                    'actions'         => $actions
                ];
            });

            return [
                'user'    => $userName,
                'modules' => $modules
            ];
        });

        return response()->json([
            'data' => $grouped
        ]);
    }

    public function showByAllUsers()
    {
        $logs = LogActivity::with('user')
            ->orderBy('created_at', 'desc')
            ->get();

        $data = [];
        $idCounter = 1;

        $logs->groupBy(fn($log) => $log->user_id)
            ->each(function ($userLogs, $userId) use (&$data, &$idCounter) {
                $userName = $userLogs->first()->user?->fullname ?? 'System';

                $userLogs->groupBy('module')
                    ->each(function ($moduleLogs, $moduleName) use (&$data, &$idCounter, $userId, $userName) {
                        $actions = $moduleLogs->groupBy('action')
                            ->map(fn($actionLogs) => $actionLogs->count());

                        // ambil log terbaru di module ini
                        $latestLog = $moduleLogs->sortByDesc('created_at')->first();

                        $data[] = [
                            'id'         => $idCounter++,
                            'user_id'    => $userId,
                            'user'       => $userName,
                            'module'     => $moduleName,
                            'create'     => $actions['create'] ?? 0,
                            'update'     => $actions['update'] ?? 0,
                            'delete'     => $actions['delete'] ?? 0,
                            'ip_address' => $latestLog->ip_address,
                            'time'       => $latestLog->created_at->diffForHumans(),
                            'timestamp'  => $latestLog->created_at->translatedFormat('d F Y H:i:s'),
                        ];
                    });
            });

        return response()->json([
            'data' => $data
        ]);
    }





        
}
