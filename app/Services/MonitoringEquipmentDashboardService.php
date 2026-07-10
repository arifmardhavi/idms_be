<?php

namespace App\Services;

use App\Helpers\BusinessPeriod;
use App\Models\MonitoringEquipment;
use App\Models\MonitoringEquipmentLog;
use Illuminate\Support\Facades\DB;

class MonitoringEquipmentDashboardService
{
    public function getDashboard(): array
    {
        $periods = BusinessPeriod::dashboardPeriods();

        return [

            'current' => $this->current(),

            'last_month' => $this->history(
                $periods['last_month']
            ),

            'two_months_ago' => $this->history(
                $periods['two_months_ago']
            )

        ];
    }

    /**
     * ===================================================
     * CURRENT
     * ===================================================
     */
    private function current(): array
    {
        $row = MonitoringEquipment::query()

            ->join(
                'tag_numbers',
                'tag_numbers.id',
                '=',
                'monitoring_equipment.tag_number_id'
            )

            ->selectRaw(
                $this->aggregateSql(
                    'monitoring_equipment.status'
                )
            )

            ->first();

        return $this->transform($row);
    }

    /**
     * ===================================================
     * HISTORY
     * ===================================================
     */
    private function history(
        string $period
    ): array
    {

        $row = MonitoringEquipmentLog::query()

            ->join(
                'tag_numbers',
                'tag_numbers.id',
                '=',
                'monitoring_equipment_logs.tag_number_id'
            )

            ->where(
                'monitoring_equipment_logs.period_code',
                $period
            )

            ->selectRaw(
                $this->aggregateSql(
                    'monitoring_equipment_logs.status'
                )
            )

            ->first();

        return $this->transform($row);

    }

    /**
     * ===================================================
     * SQL AGGREGATE
     * ===================================================
     */
    private function aggregateSql(
        string $statusColumn
    ): string {

        return "
        COUNT(*) total,

        /* ===========================
            ALL
        =========================== */

        SUM(CASE WHEN {$statusColumn}=0 THEN 1 ELSE 0 END) all_high,
        SUM(CASE WHEN {$statusColumn}=1 THEN 1 ELSE 0 END) all_medium,
        SUM(CASE WHEN {$statusColumn}=2 THEN 1 ELSE 0 END) all_low,
        SUM(CASE WHEN {$statusColumn}=3 THEN 1 ELSE 0 END) all_breakdown,

        /* ===========================
            SECE = YA
        =========================== */

        SUM(CASE WHEN tag_numbers.sece=1 AND {$statusColumn}=0 THEN 1 ELSE 0 END) sece_high,
        SUM(CASE WHEN tag_numbers.sece=1 AND {$statusColumn}=1 THEN 1 ELSE 0 END) sece_medium,
        SUM(CASE WHEN tag_numbers.sece=1 AND {$statusColumn}=2 THEN 1 ELSE 0 END) sece_low,
        SUM(CASE WHEN tag_numbers.sece=1 AND {$statusColumn}=3 THEN 1 ELSE 0 END) sece_breakdown,

        /* ===========================
            Criticality High
        =========================== */

        SUM(CASE WHEN tag_numbers.criticality=0 AND {$statusColumn}=0 THEN 1 ELSE 0 END) ch_high,
        SUM(CASE WHEN tag_numbers.criticality=0 AND {$statusColumn}=1 THEN 1 ELSE 0 END) ch_medium,
        SUM(CASE WHEN tag_numbers.criticality=0 AND {$statusColumn}=2 THEN 1 ELSE 0 END) ch_low,
        SUM(CASE WHEN tag_numbers.criticality=0 AND {$statusColumn}=3 THEN 1 ELSE 0 END) ch_breakdown,

        /* ===========================
            Criticality Medium High
        =========================== */

        SUM(CASE WHEN tag_numbers.criticality=1 AND {$statusColumn}=0 THEN 1 ELSE 0 END) cmh_high,
        SUM(CASE WHEN tag_numbers.criticality=1 AND {$statusColumn}=1 THEN 1 ELSE 0 END) cmh_medium,
        SUM(CASE WHEN tag_numbers.criticality=1 AND {$statusColumn}=2 THEN 1 ELSE 0 END) cmh_low,
        SUM(CASE WHEN tag_numbers.criticality=1 AND {$statusColumn}=3 THEN 1 ELSE 0 END) cmh_breakdown,

        /* ===========================
            Criticality Other
        =========================== */

        SUM(CASE WHEN tag_numbers.criticality IN(2,3,4) AND {$statusColumn}=0 THEN 1 ELSE 0 END) other_high,
        SUM(CASE WHEN tag_numbers.criticality IN(2,3,4) AND {$statusColumn}=1 THEN 1 ELSE 0 END) other_medium,
        SUM(CASE WHEN tag_numbers.criticality IN(2,3,4) AND {$statusColumn}=2 THEN 1 ELSE 0 END) other_low,
        SUM(CASE WHEN tag_numbers.criticality IN(2,3,4) AND {$statusColumn}=3 THEN 1 ELSE 0 END) other_breakdown
        ";

    }

    /**
     * ===================================================
     * TRANSFORM
     * ===================================================
     */
    private function transform($r): array
    {

        return [

            'all'=>$this->group(
                $r,
                'all'
            ),

            'sece_yes'=>$this->group(
                $r,
                'sece'
            ),

            'criticality_high'=>$this->group(
                $r,
                'ch'
            ),

            'criticality_medium_high'=>$this->group(
                $r,
                'cmh'
            ),

            'criticality_other'=>$this->group(
                $r,
                'other'
            )

        ];

    }

    /**
     * ===================================================
     * GROUP
     * ===================================================
     */
    private function group(
        $r,
        string $prefix
    ): array
    {

        $high = (int)$r->{$prefix.'_high'};
        $medium = (int)$r->{$prefix.'_medium'};
        $low = (int)$r->{$prefix.'_low'};
        $breakdown = (int)$r->{$prefix.'_breakdown'};

        return [

            'high'=>$high,

            'medium'=>$medium,

            'low'=>$low,

            'breakdown'=>$breakdown,

            'total'=>$high+$medium+$low+$breakdown

        ];

    }

}