<?php

namespace App\Exports;

use App\Helpers\BusinessPeriod;
use App\Models\MonitoringEquipment;
use App\Models\MonitoringEquipmentLog;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class MonitoringEquipmentChangeSheet implements FromCollection, ShouldAutoSize, WithEvents, WithHeadings, WithMapping, WithTitle
{
    protected array $filters;

    private const STATUS_COLORS = [
        'High' => ['fill' => 'C6EFCE', 'font' => '006100'],
        'Medium' => ['fill' => 'FFEB9C', 'font' => '9C5700'],
        'Low' => ['fill' => 'FFC7CE', 'font' => '9C0006'],
        'Breakdown' => ['fill' => 'C00000', 'font' => 'FFFFFF'],
        '-' => ['fill' => 'D9D9D9', 'font' => '3F3F3F'],
    ];

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function title(): string
    {
        return 'Perbandingan Periode';
    }

    public function collection()
    {
        $current = BusinessPeriod::current();
        $previous = BusinessPeriod::previous(1);

        $logs = MonitoringEquipmentLog::query()
            ->with(['tagNumber.type.category'])
            ->whereIn('period_code', [$previous['code'], $current['code']])
            ->get();

        $prevLogs = $logs
            ->where('period_code', $previous['code'])
            ->keyBy('tag_number_id')
            ->map(fn ($log) => [
                'kondisi' => $log->kondisi_peralatan,
                'status' => $log->status,
            ])
            ->all();

        $currLogs = $logs
            ->where('period_code', $current['code'])
            ->keyBy('tag_number_id')
            ->map(fn ($log) => [
                'kondisi' => $log->kondisi_peralatan,
                'status' => $log->status,
            ])
            ->all();

        $equipments = $this->equipmentQuery()
            ->get()
            ->map(fn (MonitoringEquipment $equipment) => [
                'tag_number_id' => $equipment->tag_number_id,
                'tag_number' => optional($equipment->tagNumber)->tag_number,
                'category' => $equipment->tagNumber?->type?->category?->category_name,
                'criticality' => optional($equipment->tagNumber)->criticality,
                'sece' => optional($equipment->tagNumber)->sece,
            ])
            ->all();

        return collect(static::buildRows($equipments, $prevLogs, $currLogs));
    }

    private function equipmentQuery()
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
            ->orderBy('id');
    }

    /**
     * Membandingkan status & kondisi antar periode.
     * Hanya peralatan yang BERUBAH yang dikembalikan.
     */
    public static function buildRows(array $equipments, array $prevLogs, array $currLogs): array
    {
        $rows = [];

        foreach ($equipments as $equipment) {

            $prev = $prevLogs[$equipment['tag_number_id']] ?? [];
            $curr = $currLogs[$equipment['tag_number_id']] ?? [];

            $kondisiPrev = $prev['kondisi'] ?? null;
            $kondisiCurr = $curr['kondisi'] ?? null;
            $statusPrev = static::status($prev['status'] ?? null);
            $statusCurr = static::status($curr['status'] ?? null);

            if ($kondisiPrev === $kondisiCurr && $statusPrev === $statusCurr) {
                continue;
            }

            $rows[] = [
                'tag_number' => $equipment['tag_number'],
                'category' => $equipment['category'],
                'criticality' => static::criticality($equipment['criticality'] ?? null),
                'sece' => static::sece($equipment['sece'] ?? null),
                'kondisi_prev' => $kondisiPrev ?? '-',
                'kondisi_curr' => $kondisiCurr ?? '-',
                'status_prev' => $statusPrev,
                'status_curr' => $statusCurr,
            ];
        }

        return $rows;
    }

    private static function periodLabel(string $code): string
    {
        Carbon::setLocale('id');

        return Carbon::createFromFormat('Y-m', $code)
            ->translatedFormat('F Y');
    }

    public function headings(): array
    {
        $previous = BusinessPeriod::previous(1)['code'];
        $current = BusinessPeriod::current()['code'];

        return [

            'No',

            'Tag Number',

            'Kategori Peralatan',

            'Criticality',

            'SECE',

            'Kondisi Peralatan ('.static::periodLabel($previous).')',

            'Kondisi Peralatan ('.static::periodLabel($current).')',

            'Status ('.static::periodLabel($previous).')',

            'Status ('.static::periodLabel($current).')',

        ];
    }

    public function map($row): array
    {
        static $no = 0;

        return [

            ++$no,

            $row['tag_number'],

            $row['category'],

            $row['criticality'],

            $row['sece'],

            $row['kondisi_prev'],

            $row['kondisi_curr'],

            $row['status_prev'],

            $row['status_curr'],

        ];
    }

    private static function criticality($value): ?string
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

    private static function sece($value): ?string
    {
        return match ($value) {
            null => '-',
            '0', 0 => 'Tidak',
            '1', 1 => 'Ya',
            default => '-',
        };
    }

    private static function status($value): ?string
    {
        return match ($value) {
            null, '' => '-',
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
                $sheet->setAutoFilter('A1:I'.$last);

                /**
                 * Header Style
                 */
                $sheet->getStyle('A1:I1')->applyFromArray([

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
                    $sheet->getStyle('A2:I'.$last)->applyFromArray([

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
                     * Warna Status Berbeda
                     */
                    foreach (range(2, $last) as $row) {

                        foreach (['H', 'I'] as $column) {

                            $value = (string) $sheet->getCell($column.$row)->getValue();

                            $style = static::STATUS_COLORS[$value] ?? static::STATUS_COLORS['-'];

                            $sheet->getStyle($column.$row)->applyFromArray([

                                'fill' => [
                                    'fillType' => Fill::FILL_SOLID,
                                    'startColor' => [
                                        'rgb' => $style['fill'],
                                    ],
                                ],

                                'font' => [
                                    'color' => [
                                        'rgb' => $style['font'],
                                    ],
                                ],

                            ]);

                        }

                    }

                }

                /**
                 * Column Alignment
                 */
                $sheet
                    ->getStyle('A:I')
                    ->getAlignment()
                    ->setVertical(Alignment::VERTICAL_CENTER);

                $sheet
                    ->getStyle('A2:A'.$last)
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $sheet
                    ->getStyle('H2:I'.$last)
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER);

            },

        ];
    }
}
