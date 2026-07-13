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
use PhpOffice\PhpSpreadsheet\Style\Fill;

class MonitoringEquipmentLogExport implements
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
        return 'Monitoring Equipment Logs';
    }

    public function collection()
    {
        $query = MonitoringEquipmentLog::query()
            ->with('tagNumber');

        if (!empty($this->filters['search'])) {

            $query->whereHas('tagNumber', function (Builder $q) {

                $q->where(
                    'tag_number',
                    'like',
                    '%' . $this->filters['search'] . '%'
                );

            });

        }

        if (!empty($this->filters['period_code'])) {

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

            'Snapshot'

        ];
    }

    public function map($row): array
    {
        static $no = 0;

        return [

            ++$no,

            $row->period_code,

            optional($row->tagNumber)->tag_number,

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

            optional($row->created_at)->format('d-m-Y H:i')

        ];
    }

    private function criticality($value)
    {
        return match($value){
            null=> '-',
            '0',0=>'High',
            '1',1=>'Medium High',
            '2',2=>'Medium',
            '3',3=>'Negligible',
            '4',4=>'Low',
            default=>null
        };
    }

    private function sece($value)
    {
        return match($value){
            null=> '-',
            '0',0=>'Tidak',
            '1',1=>'Ya',
            default=>null
        };
    }

    private function status($value)
    {
        return match($value){
            null=> '-',
            '0',0=>'High',
            '1',1=>'Medium',
            '2',2=>'Low',
            '3',3=>'Breakdown',
            default=>null
        };
    }

    public function registerEvents(): array
    {
        return [

            AfterSheet::class => function (AfterSheet $event) {

                $sheet = $event->sheet->getDelegate();

                $sheet->freezePane('A2');

                $sheet->getStyle('A1:Q1')
                    ->getFont()
                    ->setBold(true);

                $sheet->getStyle('A1:Q1')
                    ->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()
                    ->setRGB('D97706');

            }

        ];
    }
}