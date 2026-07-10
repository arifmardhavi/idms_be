<?php

namespace App\Services;

use App\Models\DetailRkapTa;
use App\Models\DetailRkapOh;
use App\Models\DetailRkapRt;
use App\Models\DetailRkapNr;

class DashboardRkapService
{
    public function getData($usd = null): array
    {
        // 🔥 per RKAP (per periode)
        $ta = $this->groupByPeriode(DetailRkapTa::class, $usd);
        $oh = $this->groupByPeriode(DetailRkapOh::class, $usd);
        $rt = $this->groupByPeriode(DetailRkapRt::class, $usd);
        $nr = $this->groupByPeriode(DetailRkapNr::class, $usd);

        // 🔥 total per RKAP
        $totalTa = $this->getTotal($ta);
        $totalOh = $this->getTotal($oh);
        $totalRt = $this->getTotal($rt);
        $totalNr = $this->getTotal($nr);

        $totalPerPeriode = $this->getTotalPerPeriode($ta, $oh, $rt, $nr);

        return [
            'rkap_ta' => $ta,
            'rkap_oh' => $oh,
            'rkap_rt' => $rt,
            'rkap_nr' => $nr,
            'total_per_periode' => $totalPerPeriode,

            'all_rkap' => [
                'rkap_ta' => $totalTa,
                'rkap_oh' => $totalOh,
                'rkap_rt' => $totalRt,
                'rkap_nr' => $totalNr,
            ]
        ];
    }

    /**
     * 🔥 GROUP BY PERIODE (1–12)
     */
    private function groupByPeriode($model, $usd = null): array
    {
        $rows = $model::selectRaw('periode, SUM(plan) as plan, SUM(actual) as actual')
            ->groupBy('periode')
            ->get()
            ->keyBy('periode');

        return collect(range(1, 12))->map(function ($periode) use ($rows, $usd) {

            $plan = (float) ($rows[$periode]->plan ?? 0);
            $actual = (float) ($rows[$periode]->actual ?? 0);

            // 🔥 konversi USD (kalau ada)
            if ($usd) {
                $plan = $plan / $usd;
                $actual = $actual / $usd;
            }

            return [
                'periode' => $periode,
                'plan' => round($plan, 2),
                'actual' => round($actual, 2),
                'selisih' => $this->calculatePercent($plan, $actual),
            ];
        })->values()->toArray();
    }

    /**
     * 🔥 TOTAL PER RKAP
     */
    private function getTotal(array $data): array
    {
        $plan = collect($data)->sum('plan');
        $actual = collect($data)->sum('actual');

        return [
            'plan' => round($plan, 2),
            'actual' => round($actual, 2),
            'selisih' => $this->calculatePercent($plan, $actual),
        ];
    }

    /**
     * 🔥 HITUNG PERSENTASE
     */
    private function calculatePercent($plan, $actual): float
    {
        return $plan > 0
            ? round((($plan - $actual) / $plan) * 100, 2)
            : 0;
    }

    private function getTotalPerPeriode(
        array $ta,
        array $oh,
        array $rt,
        array $nr
    ): array 
    {

        return collect(range(1, 12))->map(function ($periode) use ($ta, $oh, $rt, $nr) {

            $plan =
                ($ta[$periode - 1]['plan'] ?? 0) +
                ($oh[$periode - 1]['plan'] ?? 0) +
                ($rt[$periode - 1]['plan'] ?? 0) +
                ($nr[$periode - 1]['plan'] ?? 0);

            $actual =
                ($ta[$periode - 1]['actual'] ?? 0) +
                ($oh[$periode - 1]['actual'] ?? 0) +
                ($rt[$periode - 1]['actual'] ?? 0) +
                ($nr[$periode - 1]['actual'] ?? 0);

            return [
                'periode' => $periode,
                'plan' => round($plan, 2),
                'actual' => round($actual, 2),
                'selisih' => $this->calculatePercent($actual, $plan),
            ];
        })->toArray();
    }
}