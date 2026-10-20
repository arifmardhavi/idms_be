<?php

namespace App\Exports;

class MonitoringPipelineExport extends DynamicExport
{
    public function __construct(array $data, array $columns)
    {
        parent::__construct($data, $columns, [

            'freezeHeader' => true,

            'filter' => true,

            'headerStyle' => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FFD9E1F2'],
                ],
            ],

            'dateFormat' => [
                'tanggal_pasang_line' => 'yyyy-mm-dd',
                'tanggal_ut_terakhir' => 'yyyy-mm-dd',
                'next_plan_inspection_api' => 'yyyy-mm-dd',
                'used_plan_inspection' => 'yyyy-mm-dd',
                'estimated_end_of_life' => 'yyyy-mm-dd',
            ],

            'autoWidth' => true,

            'border' => true,

        ]);
    }
}
