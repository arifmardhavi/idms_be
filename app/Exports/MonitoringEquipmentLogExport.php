<?php

namespace App\Exports;

use App\Models\MonitoringEquipmentLog;
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

class MonitoringEquipmentLogExport implements FromCollection, ShouldAutoSize, WithEvents, WithHeadings, WithMapping, WithTitle
{
    protected array $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function title(): string
    {
        return 'Monitoring Equipment Logs';
    }

    public function collection()
    {
        $query = MonitoringEquipmentLog::query()
            ->with('tagNumber');

        if (! empty($this->filters['search'])) {

            $query->whereHas('tagNumber', function (Builder $q) {

                $q->where(
                    'tag_number',
                    'like',
                    '%'.$this->filters['search'].'%'
                );

            });

        }

        if (! empty($this->filters['period_code'])) {

            $query->where(
                'period_code',
                $this->filters['period_code']
            );

        }

        if (isset($this->filters['status'])) {

            $query->where(
                'status',
                $this->filters['status']
            );

        }

        if (isset($this->filters['criticality'])) {

            $query->where(
                'criticality',
                $this->filters['criticality']
            );

        }

        return $query
            ->latest('period_code')
            ->orderBy('tag_number_id')
            ->get();
    }

    public function headings(): array
    {
        return [

            'No',

            'Periode',

            'Tag Number',

            'Kondisi Peralatan',

            'Criticality',

            'SECE',

            'Status',

            'Jenis Kerusakan',

            'Penyebab',

            'Penanganan Sementara',

            'Perbaikan Permanen',

            'Progress',

            'Kendala',

            'Estimasi',

            'Target',

            'Period Start',

            'Period End',

            'Snapshot',

        ];
    }

    public function map($row): array
    {
        static $no = 0;

        return [

            ++$no,

            $row->period_code,

            optional($row->tagNumber)->tag_number,

            $row->kondisi_peralatan,

            $this->criticality($row->criticality),

            $this->sece($row->sece),

            $this->status($row->status),

            $row->jenis_kerusakan,

            $row->penyebab,

            $row->penanganan_sementara,

            $row->perbaikan_permanen,

            $row->progress_perbaikan_permanen,

            $row->kendala_perbaikan,

            $row->estimasi_perbaikan,

            $row->target,

            $row->period_start,

            $row->period_end,

            optional($row->created_at)->format('d-m-Y H:i'),

        ];
    }

    private function criticality($value)
    {
        return match ($value) {
            null => '-',
            '0',0 => 'High',
            '1',1 => 'Medium High',
            '2',2 => 'Medium',
            '3',3 => 'Negligible',
            '4',4 => 'Low',
            default => null
        };
    }

    private function sece($value)
    {
        return match ($value) {
            null => '-',
            '0',0 => 'Tidak',
            '1',1 => 'Ya',
            default => null
        };
    }

    private function status($value)
    {
        return match ($value) {
            null => '-',
            '0',0 => 'High',
            '1',1 => 'Medium',
            '2',2 => 'Low',
            '3',3 => 'Breakdown',
            default => null
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
                $sheet->setAutoFilter('A1:R'.$last);

                /**
                 * Header Style
                 */
                $sheet->getStyle('A1:R1')->applyFromArray([

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
                    $sheet->getStyle('A2:R'.$last)->applyFromArray([

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
                        ->getStyle('N2:N'.$last)
                        ->getNumberFormat()
                        ->setFormatCode('#,##0');

                    $sheet
                        ->getStyle('N2:N'.$last)
                        ->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_RIGHT);

                    /**
                     * Target Date Format
                     */
                    $sheet
                        ->getStyle('O2:O'.$last)
                        ->getNumberFormat()
                        ->setFormatCode('yyyy-mm-dd');

                }

                /**
                 * Column Alignment
                 */
                $sheet
                    ->getStyle('A:R')
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
                ->getCell('D'.$row)
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
                'G'.$row,
                '=IFERROR(VLOOKUP(D'.$row.','.$lookup.',2,FALSE),"")'
            );
        }
    }
}
