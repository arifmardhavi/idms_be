<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;

class MonitoringPipelineTemplateExport implements FromArray, WithHeadings, ShouldAutoSize, WithStrictNullComparison
{
    /**
     * 30 label kolom — urutan SAMA dengan format import.
     */
    public function headings(): array
    {
        return [

            'Tag Number',

            'TML Drawing Number',

            'API 570 Service Class',

            'Insulation OR Non-Insulation',

            'Grade Material',

            'NPS',

            'OD (mm)',

            'SCH',

            'T. NOM (mm)',

            'Operating Press (kg/cm2)',

            'Design Press (kg/cm2)',

            'Design Temp (kg/cm2)',

            'S (MPa)',

            'E',

            'W',

            'Y',

            'Min Req Thk (mm)',

            '50% Nom. Thk (mm)',

            'Tanggal Pasang Line',

            'Actual THK (mm)',

            'Tanggal UT Terakhir',

            'Corrosion Rate',

            'Remaining Life',

            'Alert Structural Thickness (API 574)',

            'Next Plan Inspection API 570',

            'Used Plan Inspection',

            'Pipe Condition (API 574)',

            'Estimated End Of Life (EOL)',

            'Maintenance Status',

            'Rencana Tindak Lanjut',

        ];
    }

    /**
     * Satu baris contoh data.
     */
    public function array(): array
    {
        return [

            [

                '12-5-P434-A1-H-ET',
                'J-05-1676-089',
                3,
                'No',
                'SA53 GR B',
                12,
                323.8,
                'STD',
                9.53,
                4.3,
                6.1,
                100,
                17.24,
                1.0,
                1.0,
                1.0,
                0.7,
                4.76,
                '2020-01-15',
                9.2,
                '2024-06-01',
                0.0112,
                20.5,
                2.5,
                '2029-06-01',
                '2026-06-01',
                'GOOD CONDITION',
                '2035-02-08',
                'GOOD CONDITION',
                'Periksa kembali line segera',

            ]

        ];
    }
}
