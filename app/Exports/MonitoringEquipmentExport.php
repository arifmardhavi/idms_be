<?php

namespace App\Exports;

use App\Models\MonitoringEquipment;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class MonitoringEquipmentExport implements FromCollection, ShouldAutoSize, WithEvents, WithHeadings, WithMapping, WithTitle
{
    protected array $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function title(): string
    {
        return 'Monitoring Equipment';
    }

    public function collection()
    {
        $query = MonitoringEquipment::query()
            ->with(['tagNumber.type.category']);

        /**
         * Search
         */
        if (! empty($this->filters['search'])) {

            $query->whereHas('tagNumber', function (Builder $q) {

                $q->where(
                    'tag_number',
                    'like',
                    '%'.$this->filters['search'].'%'
                );

            });

        }

        /**
         * Filter Criticality
         */
        if (isset($this->filters['criticality'])) {

            $query->whereHas('tagNumber', function (Builder $q) {

                $q->where(
                    'criticality',
                    $this->filters['criticality']
                );

            });

        }

        /**
         * Status
         */
        if (isset($this->filters['status'])) {

            $query->where(
                'status',
                $this->filters['status']
            );

        }

        /**
         * Filter SECE
         */
        if (isset($this->filters['sece'])) {

            $query->whereHas('tagNumber', function (Builder $q) {

                $q->where(
                    'sece',
                    $this->filters['sece']
                );

            });

        }

        return $query
            ->orderBy('id')
            ->get();
    }

    public function headings(): array
    {
        return [

            'No',

            'Tag Number',

            'Deskripsi Peralatan',

            'Kategori Peralatan',

            'Criticality',

            'SECE',

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

        ];
    }

    public function map($row): array
    {
        static $no = 0;
        $criticality = optional($row->tagNumber)->criticality;
        $sece = optional($row->tagNumber)->sece;
        $category = $row->tagNumber->type?->category?->category_name;

        return [

            ++$no,

            optional($row->tagNumber)->tag_number,

            optional($row->tagNumber)->description,

            $category,

            $this->criticality($criticality),

            $this->sece($sece),

            $row->kondisi_peralatan,

            $this->status($row->status),

            $row->jenis_kerusakan,

            $row->penyebab,

            $row->penanganan_sementara,

            $row->perbaikan_permanen,

            $row->progress_perbaikan_permanen,

            $row->kendala_perbaikan,

            $row->estimasi_perbaikan,

            $row->target,

        ];
    }

    private function criticality($value): ?string
    {
        return match ($value) {
            null => '-',
            '0', 0 => 'High',
            '1', 1 => 'Medium High',
            '2', 2 => 'Medium',
            '3', 3 => 'Negligible',
            '4', 4 => 'Low',
            default => '-',
        };
    }

    private function sece($value): ?string
    {
        return match ($value) {
            null => '-',
            '0', 0 => 'Tidak',
            '1', 1 => 'Ya',
            default => '-',
        };
    }

    private function status($value): ?string
    {
        return match ($value) {
            null => '-',
            'High', 0 => 'High',

            'Medium', 1 => 'Medium',

            'Low', 2 => 'Low',

            'Breakdown', 3 => 'Breakdown',

            default => '-'

        };
    }

    public function registerEvents(): array
    {
        return [

            AfterSheet::class => function (AfterSheet $event) {

                $sheet = $event->sheet->getDelegate();

                $last = $sheet->getHighestDataRow();

                /**
                 * Freeze Header
                 */
                $sheet->freezePane('A2');

                /**
                 * Auto Filter
                 */
                $sheet->setAutoFilter('A1:P'.$last);

                /**
                 * Header Style
                 */
                $sheet->getStyle('A1:P1')->applyFromArray([

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

                if ($last >= 2) {

                    /**
                     * Data Style
                     */
                    $sheet->getStyle('A2:P'.$last)->applyFromArray([

                        'alignment' => [
                            'vertical' => Alignment::VERTICAL_CENTER,
                        ],

                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => Border::BORDER_THIN,
                            ],
                        ],

                    ]);

                    /**
                     * Dropdown Kondisi Peralatan
                     */
                    $this->applyKondisiDropdown($sheet, $last);

                    /**
                     * Auto-fill Status from Kondisi Peralatan
                     */
                    $this->applyStatusFormula($sheet, $last);

                    /**
                     * Estimasi Number Format & Alignment
                     */
                    $sheet
                        ->getStyle('O2:O'.$last)
                        ->getNumberFormat()
                        ->setFormatCode('#,##0');

                    $sheet
                        ->getStyle('O2:O'.$last)
                        ->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_RIGHT);

                    /**
                     * Target Date Format
                     */
                    $sheet
                        ->getStyle('P2:P'.$last)
                        ->getNumberFormat()
                        ->setFormatCode('yyyy-mm-dd');

                }

                /**
                 * Column Alignment
                 */
                $sheet
                    ->getStyle('A:P')
                    ->getAlignment()
                    ->setVertical(Alignment::VERTICAL_CENTER);

            },

        ];
    }

    private function applyKondisiDropdown($sheet, int $last): void
    {
        $list = MonitoringEquipmentReferenceSheet::ranges()['kondisi'];

        foreach (range(2, $last) as $row) {

            $validation = $sheet
                ->getCell('G'.$row)
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

            $validation->setPromptTitle('Kondisi Peralatan');

            $validation->setPrompt('Pilih kondisi peralatan.');

            $validation->setFormula1($list);

        }
    }

    private function applyStatusFormula($sheet, int $last): void
    {
        $lookup = MonitoringEquipmentReferenceSheet::ranges()['lookup'];

        foreach (range(2, $last) as $row) {
            $sheet->setCellValue(
                'H'.$row,
                '=IFERROR(VLOOKUP(G'.$row.','.$lookup.',2,FALSE),"")'
            );
        }
    }
}
