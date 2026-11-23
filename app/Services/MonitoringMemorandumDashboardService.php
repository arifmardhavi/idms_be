<?php

namespace App\Services;

use App\Models\MonitoringMemorandum;
use App\Support\BacklogDays;
use App\Support\MonitoringMemorandumBobot;

class MonitoringMemorandumDashboardService
{
    public function getDashboard(): array
    {
        return [
            'bobot' => $this->bobot(),
            'backlog' => $this->backlog(),
        ];
    }

    private function bobot(): array
    {
        return MonitoringMemorandum::query()
            ->selectRaw('YEAR(tanggal_issue) as tahun')
            ->selectRaw(
                'AVG(' . MonitoringMemorandumBobot::scoreSql() . ') as rata_rata_bobot'
            )
            ->selectRaw('COUNT(*) as total_data')
            ->groupBy('tahun')
            ->orderBy('tahun')
            ->get()
            ->map(fn ($row) => [
                'tahun' => (int) $row->tahun,
                'rata_rata_bobot' => round((float) $row->rata_rata_bobot, 2),
                'total_data' => (int) $row->total_data,
            ])
            ->all();
    }

    private function backlog(): array
    {
        $counts = array_fill(1, 4, 0);

        MonitoringMemorandum::query()
            ->selectRaw(BacklogDays::daysSql('tanggal_issue') . ' as backlog_days')
            ->get()
            ->each(function ($row) use (&$counts) {
                $counts[BacklogDays::bucket((int) $row->backlog_days)]++;
            });

        $result = [];

        for ($bucket = 1; $bucket <= 4; $bucket++) {
            $result[] = BacklogDays::meta($bucket) + ['total' => $counts[$bucket]];
        }

        return $result;
    }
}
