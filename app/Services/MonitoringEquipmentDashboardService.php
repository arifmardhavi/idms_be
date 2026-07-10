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
    private function aggregateSql(string $statusColumn): string
    {
        return "

        /* ============================================================
            ALL
        ============================================================ */

        COUNT(*) as total,

        SUM(CASE WHEN {$statusColumn}=0 THEN 1 ELSE 0 END) as all_high,
        SUM(CASE WHEN {$statusColumn}=1 THEN 1 ELSE 0 END) as all_medium,
        SUM(CASE WHEN {$statusColumn}=2 THEN 1 ELSE 0 END) as all_low,
        SUM(CASE WHEN {$statusColumn}=3 THEN 1 ELSE 0 END) as all_breakdown,

        /* ============================================================
            SECE (PRIORITAS PERTAMA)
        ============================================================ */

        SUM(CASE
            WHEN tag_numbers.sece = 1
            AND {$statusColumn}=0
            THEN 1 ELSE 0
        END) as sece_high,

        SUM(CASE
            WHEN tag_numbers.sece = 1
            AND {$statusColumn}=1
            THEN 1 ELSE 0
        END) as sece_medium,

        SUM(CASE
            WHEN tag_numbers.sece = 1
            AND {$statusColumn}=2
            THEN 1 ELSE 0
        END) as sece_low,

        SUM(CASE
            WHEN tag_numbers.sece = 1
            AND {$statusColumn}=3
            THEN 1 ELSE 0
        END) as sece_breakdown,

        /* ============================================================
            CRITICALITY HIGH
            (HANYA YANG BUKAN SECE)
        ============================================================ */

        SUM(CASE
            WHEN tag_numbers.sece = 0
            AND tag_numbers.criticality = 0
            AND {$statusColumn}=0
            THEN 1 ELSE 0
        END) as ch_high,

        SUM(CASE
            WHEN tag_numbers.sece = 0
            AND tag_numbers.criticality = 0
            AND {$statusColumn}=1
            THEN 1 ELSE 0
        END) as ch_medium,

        SUM(CASE
            WHEN tag_numbers.sece = 0
            AND tag_numbers.criticality = 0
            AND {$statusColumn}=2
            THEN 1 ELSE 0
        END) as ch_low,

        SUM(CASE
            WHEN tag_numbers.sece = 0
            AND tag_numbers.criticality = 0
            AND {$statusColumn}=3
            THEN 1 ELSE 0
        END) as ch_breakdown,

        /* ============================================================
            CRITICALITY MEDIUM HIGH
            (HANYA YANG BUKAN SECE)
        ============================================================ */

        SUM(CASE
            WHEN tag_numbers.sece = 0
            AND tag_numbers.criticality = 1
            AND {$statusColumn}=0
            THEN 1 ELSE 0
        END) as cmh_high,

        SUM(CASE
            WHEN tag_numbers.sece = 0
            AND tag_numbers.criticality = 1
            AND {$statusColumn}=1
            THEN 1 ELSE 0
        END) as cmh_medium,

        SUM(CASE
            WHEN tag_numbers.sece = 0
            AND tag_numbers.criticality = 1
            AND {$statusColumn}=2
            THEN 1 ELSE 0
        END) as cmh_low,

        SUM(CASE
            WHEN tag_numbers.sece = 0
            AND tag_numbers.criticality = 1
            AND {$statusColumn}=3
            THEN 1 ELSE 0
        END) as cmh_breakdown,

        /* ============================================================
            CRITICALITY OTHER
            (Secondary Medium, Negligible, Low)
            HANYA YANG BUKAN SECE
        ============================================================ */

        SUM(CASE
            WHEN tag_numbers.sece = 0
            AND tag_numbers.criticality IN (2,3,4)
            AND {$statusColumn}=0
            THEN 1 ELSE 0
        END) as other_high,

        SUM(CASE
            WHEN tag_numbers.sece = 0
            AND tag_numbers.criticality IN (2,3,4)
            AND {$statusColumn}=1
            THEN 1 ELSE 0
        END) as other_medium,

        SUM(CASE
            WHEN tag_numbers.sece = 0
            AND tag_numbers.criticality IN (2,3,4)
            AND {$statusColumn}=2
            THEN 1 ELSE 0
        END) as other_low,

        SUM(CASE
            WHEN tag_numbers.sece = 0
            AND tag_numbers.criticality IN (2,3,4)
            AND {$statusColumn}=3
            THEN 1 ELSE 0
        END) as other_breakdown

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

            /**
             * Semua Data
             */
            'all' => [

                'high' => (int) $r->all_high,

                'medium' => (int) $r->all_medium,

                'low' => (int) $r->all_low,

                'breakdown' => (int) $r->all_breakdown,

                'total' => (int) $r->total,

            ],

            /**
             * Status High
             */
            'high' => [

                'sece_yes' => (int) $r->sece_high,

                'criticality_high' => (int) $r->ch_high,

                'criticality_medium_high' => (int) $r->cmh_high,

                'criticality_other' => (int) $r->other_high,

                'total' =>
                    (int) $r->sece_high +
                    (int) $r->ch_high +
                    (int) $r->cmh_high +
                    (int) $r->other_high,

            ],

            /**
             * Status Medium
             */
            'medium' => [

                'sece_yes' => (int) $r->sece_medium,

                'criticality_high' => (int) $r->ch_medium,

                'criticality_medium_high' => (int) $r->cmh_medium,

                'criticality_other' => (int) $r->other_medium,

                'total' =>
                    (int) $r->sece_medium +
                    (int) $r->ch_medium +
                    (int) $r->cmh_medium +
                    (int) $r->other_medium,

            ],

            /**
             * Status Low
             */
            'low' => [

                'sece_yes' => (int) $r->sece_low,

                'criticality_high' => (int) $r->ch_low,

                'criticality_medium_high' => (int) $r->cmh_low,

                'criticality_other' => (int) $r->other_low,

                'total' =>
                    (int) $r->sece_low +
                    (int) $r->ch_low +
                    (int) $r->cmh_low +
                    (int) $r->other_low,

            ],

            /**
             * Status Breakdown
             */
            'breakdown' => [

                'sece_yes' => (int) $r->sece_breakdown,

                'criticality_high' => (int) $r->ch_breakdown,

                'criticality_medium_high' => (int) $r->cmh_breakdown,

                'criticality_other' => (int) $r->other_breakdown,

                'total' =>
                    (int) $r->sece_breakdown +
                    (int) $r->ch_breakdown +
                    (int) $r->cmh_breakdown +
                    (int) $r->other_breakdown,

            ],

        ];
    }



    // private function transform($r): array
    // {

    //     return [

    //         'all'=>$this->group(
    //             $r,
    //             'all'
    //         ),

    //         'sece_yes'=>$this->group(
    //             $r,
    //             'sece'
    //         ),

    //         'criticality_high'=>$this->group(
    //             $r,
    //             'ch'
    //         ),

    //         'criticality_medium_high'=>$this->group(
    //             $r,
    //             'cmh'
    //         ),

    //         'criticality_other'=>$this->group(
    //             $r,
    //             'other'
    //         )

    //     ];

    // }

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