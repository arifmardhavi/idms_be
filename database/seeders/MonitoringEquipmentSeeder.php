<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MonitoringEquipment;

class MonitoringEquipmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = [
            [
                'tag_number_id' => 2527,
                'criticality' => 'bad',
                'sece' => 1,
                'status' => 1,
                'tindak_lanjut' => null,
                'target' => '2026-06-30',
            ],
            [
                'tag_number_id' => 2528,
                'criticality' => 'good',
                'sece' => 0,
                'status' => 1,
                'tindak_lanjut' => 'Inspection',
                'target' => '2026-07-15',
            ],
            [
                'tag_number_id' => 2529,
                'criticality' => 'medium',
                'sece' => 1,
                'status' => 0,
                'tindak_lanjut' => 'Repair',
                'target' => '2026-08-01',
            ],
        ];

        foreach ($data as $item) {
            MonitoringEquipment::updateOrCreate(
                ['tag_number_id' => $item['tag_number_id']],
                $item
            );
        }
    }
}