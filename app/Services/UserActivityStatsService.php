<?php

namespace App\Services;

use App\Models\LogActivity;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Menghitung agregat aktivitas user (log_activities) secara bulk.
 *
 * Dipakai supaya serialisasi collection user tidak melakukan query per user.
 */
class UserActivityStatsService
{
    /**
     * Agregat aktivitas untuk banyak user sekaligus (1 query).
     *
     * @param  array<int>  $userIds
     * @return array<int, array{total:int, per_feature:array<string,int>}>
     */
    public function forUsers(array $userIds): array
    {
        $userIds = array_values(array_unique(array_map('intval', $userIds)));

        if (empty($userIds)) {
            return [];
        }

        $rows = LogActivity::whereIn('user_id', $userIds)
            ->select('user_id', 'module', DB::raw('COUNT(*) as total'))
            ->groupBy('user_id', 'module')
            ->get();

        $stats = [];

        foreach ($rows as $row) {
            $userId = (int) $row->user_id;
            $total = (int) $row->total;

            $stats[$userId]['total'] = ($stats[$userId]['total'] ?? 0) + $total;
            $stats[$userId]['per_feature'][$row->module] = $total;
        }

        return $stats;
    }

    /**
     * Pasang hasil agregat ke model user agar accessor pakai nilai yang
     * sudah dihitung, bukan query sendiri per user.
     *
     * @param  Collection<int, \App\Models\User>  $users
     */
    public function attachTo(Collection $users): void
    {
        $stats = $this->forUsers($users->modelKeys());

        foreach ($users as $user) {
            $user->setActivityStats(
                $stats[$user->id]['total'] ?? 0,
                $stats[$user->id]['per_feature'] ?? []
            );
        }
    }
}
