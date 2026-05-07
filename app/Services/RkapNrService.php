<?php

namespace App\Services;

use App\Models\DetailRkapNr;
use App\Models\RkapNr;
use Illuminate\Support\Facades\DB;

class RkapNrService
{
    public function getSummary(): array
    {
        $grandTotal = (int) DetailRkapNr::sum('actual');

        $totalPerPeriod = DetailRkapNr::select(
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

    public function store(array $data): RkapNr
    {
        return DB::transaction(function () use ($data) {

            // create master
            $rkap = RkapNr::create([
                'judul' => $data['judul'],
            ]);

            // prepare detail (bulk insert)
            $details = collect($data['data_periode'])->map(function ($item) use ($rkap) {
                return [
                    'rkap_nr_id' => $rkap->id,
                    'periode' => $item['periode'],
                    'plan' => $item['plan'] ?? 0,
                    'actual' => $item['actual'] ?? 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            })->toArray();

            DetailRkapNr::insert($details);

            return $rkap->load('detailRkapNr');
        });
    }

    public function getSummaryByRkap(int $rkapId): array
    {
        $grandTotal = (int) DetailRkapNr::where('rkap_nr_id', $rkapId)
            ->sum('actual');

        $totalPerPeriod = DetailRkapNr::where('rkap_nr_id', $rkapId)
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
            DetailRkapNr::where('rkap_nr_id', $rkap->id)->delete();

            // mapping input
            $input = collect($data['data_periode'])->keyBy('periode');

            // insert ulang (1–12 biar konsisten)
            $details = collect(range(1, 12))->map(function ($periode) use ($input, $rkap) {
                return [
                    'rkap_nr_id' => $rkap->id,
                    'periode' => $periode,
                    'plan' => $input[$periode]['plan'] ?? 0,
                    'actual' => $input[$periode]['actual'] ?? 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            })->toArray();

            DetailRkapNr::insert($details);

            return $rkap->load('detailRkapNr');
        });
    }
}