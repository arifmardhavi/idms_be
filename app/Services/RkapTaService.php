<?php

namespace App\Services;

use App\Models\DetailRkapTa;
use App\Models\RkapTa;
use Illuminate\Support\Facades\DB;

class RkapTaService
{
    public function getSummary(): array
    {
        $grandTotal = DetailRkapTa::selectRaw('
                SUM(plan) as total_plan,
                SUM(actual) as total_actual
            ')
            ->first();

        $totalPerPeriod = DetailRkapTa::select(
                'periode',
                DB::raw('SUM(plan) as total_plan'),
                DB::raw('SUM(actual) as total_actual')
            )
            ->groupBy('periode')
            ->get()
            ->keyBy('periode');

        $formatted = collect(range(1, 12))->map(function ($periode) use ($totalPerPeriod) {

            $row = $totalPerPeriod->get($periode);

            return [
                'periode' => $periode,
                'total_plan' => (int) ($row->total_plan ?? 0),
                'total_actual' => (int) ($row->total_actual ?? 0),
            ];
        })->values();

        return [
            'total_all_periode' => [
                'total_plan' => (int) ($grandTotal->total_plan ?? 0),
                'total_actual' => (int) ($grandTotal->total_actual ?? 0),
            ],

            'total_per_periode' => $formatted,
        ];
    }

    public function store(array $data): RkapTa
    {
        return DB::transaction(function () use ($data) {

            // 🔥 create master
            $rkap = RkapTa::create([
                'judul' => $data['judul'],
            ]);

            // 🔥 prepare detail (bulk insert)
            $details = collect($data['data_periode'])->map(function ($item) use ($rkap) {
                return [
                    'rkap_ta_id' => $rkap->id,
                    'periode' => $item['periode'],
                    'plan' => $item['plan'] ?? 0,
                    'actual' => $item['actual'] ?? 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            })->toArray();

            DetailRkapTa::insert($details);

            return $rkap->load('detailRkapTa');
        });
    }

    public function getSummaryByRkap(int $rkapId): array
    {
        $grandTotal = DetailRkapTa::where('rkap_ta_id', $rkapId)
            ->selectRaw('
                SUM(plan) as total_plan,
                SUM(actual) as total_actual
            ')
            ->first();

        $totalPerPeriod = DetailRkapTa::where('rkap_ta_id', $rkapId)
            ->select(
                'periode',
                DB::raw('SUM(plan) as total_plan'),
                DB::raw('SUM(actual) as total_actual')
            )
            ->groupBy('periode')
            ->get()
            ->keyBy('periode');

        $formatted = collect(range(1, 12))->map(function ($periode) use ($totalPerPeriod) {

            $row = $totalPerPeriod->get($periode);

            return [
                'periode' => $periode,
                'total_plan' => (int) ($row->total_plan ?? 0),
                'total_actual' => (int) ($row->total_actual ?? 0),
            ];
        })->values();

        return [
            'total_all_periode' => [
                'total_plan' => (int) ($grandTotal->total_plan ?? 0),
                'total_actual' => (int) ($grandTotal->total_actual ?? 0),
            ],

            'total_per_periode' => $formatted,
        ];
    }

    public function update($rkap, array $data)
    {
        return DB::transaction(function () use ($rkap, $data) {

            // 🔥 update master
            $rkap->update([
                'judul' => $data['judul'],
            ]);

            // 🔥 delete detail lama
            DetailRkapTa::where('rkap_ta_id', $rkap->id)->delete();

            // 🔥 mapping input
            $input = collect($data['data_periode'])->keyBy('periode');

            // 🔥 insert ulang (1–12 biar konsisten)
            $details = collect(range(1, 12))->map(function ($periode) use ($input, $rkap) {
                return [
                    'rkap_ta_id' => $rkap->id,
                    'periode' => $periode,
                    'plan' => $input[$periode]['plan'] ?? 0,
                    'actual' => $input[$periode]['actual'] ?? 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            })->toArray();

            DetailRkapTa::insert($details);

            return $rkap->load('detailRkapTa');
        });
    }
}