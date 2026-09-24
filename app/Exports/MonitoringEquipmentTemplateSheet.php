<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class MonitoringEquipmentTemplateSheet implements FromCollection, ShouldAutoSize, WithEvents, WithTitle
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
                'Kondisi Peralatan',
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
                '',
            ],

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
                $sheet->getStyle('A1:K1')->applyFromArray([

                    'font' => [
                        'bold' => true,
                        'size' => 11,
                        'color' => [
                            'rgb' => 'FFFFFF',
                        ],
                    ],

                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],

                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => [
                            'rgb' => 'D97706',
                        ],
                    ],

                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                        ],
                    ],

                ]);

                /**
                 * Header Height
                 */
                $sheet->getRowDimension(1)->setRowHeight(25);

                /**
                 * Background Input Area
                 */
                $sheet->getStyle('A2:K5')->applyFromArray([

                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => [
                            'rgb' => 'FFFDF5',
                        ],
                    ],

                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                        ],
                    ],
                ]);

                /**
                 * Number Format
                 */
                $sheet
                    ->getStyle('J2:J1000')
                    ->getNumberFormat()
                    ->setFormatCode('#,##0');

                /**
                 * Target Date Format
                 */
                $sheet
                    ->getStyle('K2:K1000')
                    ->getNumberFormat()
                    ->setFormatCode('yyyy-mm-dd');

                /**
                 * Column Alignment
                 */
                $sheet
                    ->getStyle('A:K')
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
                    ->getStyle('J:J')
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_RIGHT);

                /**
                 * Dropdown Status
                 */
                $this->applyDropdown(

                    $sheet,

                    'C{row}',

                    '=Reference!$B$2:$B$5',

                    'Status',

                    'Pilih nilai Status.'

                );
            },

        ];
    }

    private function applyDropdown(
        $sheet,
        string $cellRange,
        string $formula,
        string $title,
        string $message
    ): void {

        foreach (range(2, 1000) as $row) {

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
