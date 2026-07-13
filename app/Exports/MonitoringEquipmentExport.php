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
use PhpOffice\PhpSpreadsheet\Style\Fill;

class MonitoringEquipmentExport implements
    FromCollection,
    WithHeadings,
    WithMapping,
    ShouldAutoSize,
    WithEvents,
    WithTitle
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
            ->with('tagNumber');

        /**
         * Search
         */
        if (!empty($this->filters['search'])) {

            $query->whereHas('tagNumber', function (Builder $q) {

                $q->where(
                    'tag_number',
                    'like',
                    '%' . $this->filters['search'] . '%'
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

            'Criticality',

            'SECE',

            'Status',

            'Jenis Kerusakan',

            'Penyebab',

            'Penanganan Sementara',

            'Perbaikan Permanen',

            'Progress Perbaikan Permanen',

            'Kendala Perbaikan',

            'Estimasi Perbaikan',

            'Target',

            'Updated At',

        ];
    }

    public function map($row): array
    {
        static $no = 0;
        $criticality = optional($row->tagNumber)->criticality;
        $sece = optional($row->tagNumber)->sece;

        return [

            ++$no,

            optional($row->tagNumber)->tag_number,

            $this->criticality($criticality),

            $this->sece($sece),

            $this->status($row->status),

            $row->jenis_kerusakan,

            $row->penyebab,

            $row->penanganan_sementara,

            $row->perbaikan_permanen,

            $row->progress_perbaikan_permanen,

            $row->kendala_perbaikan,

            $row->estimasi_perbaikan,

            $row->target,

            optional($row->updated_at)->format('d-m-Y H:i'),

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
            '0', 0 => 'High',

            '1', 1 => 'Medium',

            '2', 2 => 'Low',

            '3', 3 => 'Breakdown',

            default => '-'

        };
    }

    public function registerEvents(): array
    {
        return [

            AfterSheet::class => function (AfterSheet $event) {

                $sheet = $event->sheet->getDelegate();

                $sheet->freezePane('A2');

                $sheet->getStyle('A1:N1')
                    ->getFont()
                    ->setBold(true);

                $sheet->getStyle('A1:N1')
                    ->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()
                    ->setRGB('D97706');

            }

        ];
    }
}