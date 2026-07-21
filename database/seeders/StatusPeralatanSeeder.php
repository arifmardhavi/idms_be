<?php

namespace Database\Seeders;

use App\Models\StatusPeralatan;
use Illuminate\Database\Seeder;

class StatusPeralatanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = [
            [
                'status_peralatan' => 'High',
                'is_active' => 1,
            ],
            [
                'status_peralatan' => 'Medium',
                'is_active' => 1,
            ],
            [
                'status_peralatan' => 'Low',
                'is_active' => 1,
            ],
            [
                'status_peralatan' => 'Breakdown',
                'is_active' => 1,
            ],
        ];

        foreach ($data as $item) {
            StatusPeralatan::updateOrCreate(
                [
                    'status_peralatan' => $item['status_peralatan'],
                ],
                [
                    'is_active' => $item['is_active'],
                ]
            );
        }
    }
}