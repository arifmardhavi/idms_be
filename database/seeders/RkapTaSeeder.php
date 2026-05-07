<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\RkapTa;
use App\Models\DetailRkapTa;

class RkapTaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $rkapList = [
            [
                'judul' => 'RKAP TA 2025',
            ],
            [
                'judul' => 'RKAP Maintenance',
            ],
            [
                'judul' => 'RKAP Produksi',
            ],
        ];

        foreach ($rkapList as $item) {

            // 🔥 create master
            $rkap = RkapTa::create([
                'judul' => $item['judul'],
            ]);

            // 🔥 create detail periode 1–12
            $details = collect(range(1, 12))->map(function ($periode) use ($rkap) {

                $plan = rand(1000000, 5000000);
                $actual = rand(500000, $plan);

                return [
                    'rkap_ta_id' => $rkap->id,
                    'periode' => $periode,
                    'plan' => $plan,
                    'actual' => $actual,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            })->toArray();

            DetailRkapTa::insert($details);
        }
    }
}