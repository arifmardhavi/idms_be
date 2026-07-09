<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;

class MonitoringEquipmentTemplateSheet implements
    FromCollection,
    ShouldAutoSize,
    WithEvents,
    WithTitle
{
    public function title(): string
    {
        return 'Monitoring Equipment';
    }

    public function collection()
    {
        return collect([

            [
                'Tag Number',
                'Status',
                'Jenis Kerusakan',
                'Penyebab',
                'Penanganan Sementara',
                'Perbaikan Permanen',
                'Progress Perbaikan Permanen',
                'Kendala Perbaikan',
                'Estimasi Perbaikan',
                'Target',
            ],

            [
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
            ]

        ]);
    }

    public function registerEvents(): array
    {
        return [

            AfterSheet::class => function (AfterSheet $event) {

                $sheet = $event->sheet->getDelegate();

                /**
                 * Freeze Header
                 */
                $sheet->freezePane('A2');

                /**
                 * Header Style
                 */
                $sheet->getStyle('A1:J1')->applyFromArray([

                    'font' => [
                        'bold' => true,
                        'size' => 11,
                        'color' => [
                            'rgb' => 'FFFFFF'
                        ]
                    ],

                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],

                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => [
                            'rgb' => 'D97706'
                        ]
                    ],

                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN
                        ]
                    ]

                ]);

                /**
                 * Header Height
                 */
                $sheet->getRowDimension(1)->setRowHeight(25);

                /**
                 * Background Input Area
                 */
                $sheet->getStyle('A2:J5')->applyFromArray([

                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => [
                            'rgb' => 'FFFDF5'
                        ]
                    ],

                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN
                        ]
                    ]
                ]);

                /**
                 * Number Format
                 */
                $sheet
                    ->getStyle('I2:I1000')
                    ->getNumberFormat()
                    ->setFormatCode('#,##0');

                /**
                 * Column Alignment
                 */
                $sheet
                    ->getStyle('A:J')
                    ->getAlignment()
                    ->setVertical(Alignment::VERTICAL_CENTER);

                /**
                 * Center Column
                 */
                // $sheet
                //     ->getStyle('B:D')
                //     ->getAlignment()
                //     ->setHorizontal(Alignment::HORIZONTAL_CENTER);

                /**
                 * Estimasi Center
                 */
                $sheet
                    ->getStyle('I:I')
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_RIGHT);



                /**
                 * Dropdown Status
                 */
                $this->applyDropdown(

                    $sheet,

                    'B{row}',

                    '=Reference!$B$2:$B$5',

                    'Status',

                    'Pilih nilai Status.'

                );
            }

        ];
    }

    private function applyDropdown(
        $sheet,
        string $cellRange,
        string $formula,
        string $title,
        string $message
    ): void {

        foreach (range(2,1000) as $row) {

            $validation = $sheet
                ->getCell(
                    str_replace('{row}', $row, $cellRange)
                )
                ->getDataValidation();

            $validation->setType(
                DataValidation::TYPE_LIST
            );

            $validation->setErrorStyle(
                DataValidation::STYLE_STOP
            );

            $validation->setAllowBlank(true);

            $validation->setShowDropDown(true);

            $validation->setShowInputMessage(true);

            $validation->setShowErrorMessage(true);

            $validation->setErrorTitle('Input Tidak Valid');

            $validation->setError('Silakan pilih nilai dari dropdown.');

            $validation->setPromptTitle($title);

            $validation->setPrompt($message);

            $validation->setFormula1($formula);

        }

    }
}