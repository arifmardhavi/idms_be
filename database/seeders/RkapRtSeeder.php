<?php

namespace Database\Seeders;

use App\Models\DetailRkapRt;
use App\Models\RkapRt;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RkapRtSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $rkapList = [
            [
                'judul' => 'RKAP RT 2025',
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
            $rkap = RkapRt::create([
                'judul' => $item['judul'],
            ]);

            // 🔥 create detail periode 1–12
            $details = collect(range(1, 12))->map(function ($periode) use ($rkap) {

                $plan = rand(1000000, 5000000);
                $actual = rand(500000, $plan);

                return [
                    'rkap_rt_id' => $rkap->id,
                    'periode' => $periode,
                    'plan' => $plan,
                    'actual' => $actual,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            })->toArray();

            DetailRkapRt::insert($details);
        }
    }
}
