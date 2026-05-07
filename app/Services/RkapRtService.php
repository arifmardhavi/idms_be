<?php

namespace App\Services;

use App\Models\DetailRkapRt;
use App\Models\RkapRt;
use Illuminate\Support\Facades\DB;

class RkapRtService
{
    public function getSummary(): array
    {
        $grandTotal = (int) DetailRkapRt::sum('actual');

        $totalPerPeriod = DetailRkapRt::select(
                'periode',
                DB::raw('SUM(actual) as total')
            )
            ->groupBy('periode')
            ->pluck('total', 'periode')
            ->toArray();

        $formatted = collect(range(1, 12))->map(function ($periode) use ($totalPerPeriod) {
            return [
                'periode' => (int) $periode,
                'total' => (int) ($totalPerPeriod[$periode] ?? 0),
            ];
        })->values();

        return [
            'total_all_periode' => $grandTotal,
            'total_per_periode' => $formatted,
        ];
    }

    public function store(array $data): RkapRt
    {
        return DB::transaction(function () use ($data) {

            // create master
            $rkap = RkapRt::create([
                'judul' => $data['judul'],
            ]);

            // prepare detail (bulk insert)
            $details = collect($data['data_periode'])->map(function ($item) use ($rkap) {
                return [
                    'rkap_rt_id' => $rkap->id,
                    'periode' => $item['periode'],
                    'plan' => $item['plan'] ?? 0,
                    'actual' => $item['actual'] ?? 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            })->toArray();

            DetailRkapRt::insert($details);

            return $rkap->load('detailRkapRt');
        });
    }

    public function getSummaryByRkap(int $rkapId): array
    {
        $grandTotal = (int) DetailRkapRt::where('rkap_rt_id', $rkapId)
            ->sum('actual');

        $totalPerPeriod = DetailRkapRt::where('rkap_rt_id', $rkapId)
            ->select('periode', DB::raw('SUM(actual) as total'))
            ->groupBy('periode')
            ->pluck('total', 'periode')
            ->toArray();

        $formatted = collect(range(1, 12))->map(function ($periode) use ($totalPerPeriod) {
            return [
                'periode' => (int) $periode,
                'total' => (int) ($totalPerPeriod[$periode] ?? 0),
            ];
        })->values();

        return [
            'total_all_periode' => $grandTotal,
            'total_per_periode' => $formatted,
        ];
    }

    public function update($rkap, array $data)
    {
        return DB::transaction(function () use ($rkap, $data) {

            // update master
            $rkap->update([
                'judul' => $data['judul'],
            ]);

            // delete detail lama
            DetailRkapRt::where('rkap_rt_id', $rkap->id)->delete();

            // mapping input
            $input = collect($data['data_periode'])->keyBy('periode');

            // insert ulang (1–12 biar konsisten)
            $details = collect(range(1, 12))->map(function ($periode) use ($input, $rkap) {
                return [
                    'rkap_rt_id' => $rkap->id,
                    'periode' => $periode,
                    'plan' => $input[$periode]['plan'] ?? 0,
                    'actual' => $input[$periode]['actual'] ?? 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            })->toArray();

            DetailRkapRt::insert($details);

            return $rkap->load('detailRkapRt');
        });
    }
}