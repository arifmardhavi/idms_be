<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class MonitoringEquipmentReferenceSheet implements
    FromArray,
    ShouldAutoSize,
    WithTitle,
    WithEvents
{
    public function title(): string
    {
        return 'Reference';
    }

    public function array(): array
    {
        return [

            [
                'Status',
                'Description'
            ],

            ['0', 'High'],
            ['1', 'Medium'],
            ['2', 'Low'],
            ['3', 'Breakdown'],

        ];
    }

    public function registerEvents(): array
    {
        return [

            AfterSheet::class => function (AfterSheet $event) {

                $sheet = $event->sheet->getDelegate();

                /**
                 * Header
                 */
                $sheet->getStyle('A1:C1')->getFont()->setBold(true);

                $sheet->getStyle('A1:C1')
                    ->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()
                    ->setRGB('D97706');

                /**
                 * Protect Sheet
                 */
                $sheet->getProtection()->setSheet(true);

                $sheet->getProtection()->setPassword('idms');

            }

        ];
    }
}