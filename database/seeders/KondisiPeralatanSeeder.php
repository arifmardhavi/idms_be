<?php

namespace Database\Seeders;

use App\Models\KondisiPeralatan;
use Illuminate\Database\Seeder;

class KondisiPeralatanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = [
            [
                'kondisi_peralatan' => 'Terjadi Leak',
                'status' => 'Breakdown',
                'is_active' => 1,
            ],
            [
                'kondisi_peralatan' => 'Out Of Service / Breakdown',
                'status' => 'Breakdown',
                'is_active' => 1,
            ],
            [
                'kondisi_peralatan' => 'Tidak Ada Leak, Major Defect',
                'status' => 'Low',
                'is_active' => 1,
            ],
            [
                'kondisi_peralatan' => 'Tidak Ada Leak, Overdue >= 6 Bulan',
                'status' => 'Low',
                'is_active' => 1,
            ],
            [
                'kondisi_peralatan' => 'Tidak Ada Leak, Minor Defect',
                'status' => 'Medium',
                'is_active' => 1,
            ],
            [
                'kondisi_peralatan' => 'Tidak Ada Leak, Overdue < 6 Bulan',
                'status' => 'Medium',
                'is_active' => 1,
            ],
            [
                'kondisi_peralatan' => 'No Issue',
                'status' => 'High',
                'is_active' => 1,
            ],
        ];

        foreach ($data as $item) {
            KondisiPeralatan::updateOrCreate(
                [
                    'kondisi_peralatan' => $item['kondisi_peralatan'],
                ],
                [
                    'status' => $item['status'],
                    'is_active' => $item['is_active'],
                ]
            );
        }
    }
}