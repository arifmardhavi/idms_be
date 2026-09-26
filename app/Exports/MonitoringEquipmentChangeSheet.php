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

    protected bool $includeUnchanged = false;

    protected int $periodCount = 2;

    protected int $no = 0;

    private const STATUS_COLORS = [
        'High' => ['fill' => 'C6EFCE', 'font' => '006100'],
        'Medium' => ['fill' => 'FFEB9C', 'font' => '9C5700'],
        'Low' => ['fill' => 'FFC7CE', 'font' => '9C0006'],
        'Breakdown' => ['fill' => 'C00000', 'font' => 'FFFFFF'],
        '-' => ['fill' => 'D9D9D9', 'font' => '3F3F3F'],
    ];

    public function __construct(array $filters = [], bool $includeUnchanged = false, int $periodCount = 2)
    {
        $this->filters = $filters;
        $this->includeUnchanged = $includeUnchanged;
        $this->periodCount = $periodCount;
    }

    public function title(): string
    {
        return $this->includeUnchanged ? 'Perbandingan Periode (Semua)' : 'Perbandingan Periode';
    }

    public function collection()
    {
        $current = BusinessPeriod::current();
        $previous = BusinessPeriod::previous(1);

        $periods = $this->periodCount === 3
            ? [BusinessPeriod::previous(2), $previous, $current]
            : [$previous, $current];

        $codes = array_column($periods, 'code');

        $logs = MonitoringEquipmentLog::query()
            ->with(['tagNumber.type.category'])
            ->whereIn('period_code', $codes)
            ->get();

        $logsByPeriod = [];

        foreach ($periods as $period) {
            $logsByPeriod[] = $logs
                ->where('period_code', $period['code'])
                ->keyBy('tag_number_id')
                ->map(fn ($log) => [
                    'kondisi' => $log->kondisi_peralatan,
                    'status' => $log->status,
                ])
                ->all();
        }

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

        if ($this->periodCount === 3) {
            return collect(static::buildRows3($equipments, $logsByPeriod, $this->includeUnchanged));
        }

        return collect(static::buildRows($equipments, $logsByPeriod[0], $logsByPeriod[1], $this->includeUnchanged));
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
     * Hanya peralatan yang BERUBAH yang dikembalikan,
     * kecuali $includeUnchanged = true (semua peralatan).
     */
    public static function buildRows(array $equipments, array $prevLogs, array $currLogs, bool $includeUnchanged = false): array
    {
        $rows = [];

        foreach ($equipments as $equipment) {

            $prev = $prevLogs[$equipment['tag_number_id']] ?? [];
            $curr = $currLogs[$equipment['tag_number_id']] ?? [];

            $kondisiPrev = $prev['kondisi'] ?? null;
            $kondisiCurr = $curr['kondisi'] ?? null;
            $statusPrev = static::status($prev['status'] ?? null);
            $statusCurr = static::status($curr['status'] ?? null);

            if (! $includeUnchanged && $kondisiPrev === $kondisiCurr && $statusPrev === $statusCurr) {
                continue;
            }

            $rows[] = [
                'tag_number' => $equipment['tag_number'],
                'category' => $equipment['category'],
                'criticality' => static::criticality($equipment['criticality'] ?? null),
                'sece' => static::sece($equipment['sece'] ?? null),
                'kondisi1' => $kondisiPrev ?? '-',
                'kondisi2' => $kondisiCurr ?? '-',
                'status1' => $statusPrev,
                'status2' => $statusCurr,
            ];
        }

        return $rows;
    }

    /**
     * Membandingkan kondisi & status antar 3 periode (terlama -> terbaru).
     * Semua peralatan dikembalikan, kecuali $includeUnchanged = false
     * (hanya yang berubah).
     */
    public static function buildRows3(array $equipments, array $logsByPeriod, bool $includeUnchanged = false): array
    {
        $rows = [];

        foreach ($equipments as $equipment) {

            $cells = [];
            $changed = false;
            $prev = null;

            foreach ($logsByPeriod as $periodLogs) {

                $log = $periodLogs[$equipment['tag_number_id']] ?? [];

                $kondisi = $log['kondisi'] ?? null;
                $status = static::status($log['status'] ?? null);

                if ($prev !== null && ($prev['kondisi'] !== $kondisi || $prev['status'] !== $status)) {
                    $changed = true;
                }

                $prev = ['kondisi' => $kondisi, 'status' => $status];

                $cells[] = [
                    'kondisi' => $kondisi ?? '-',
                    'status' => $status,
                ];

            }

            if (! $includeUnchanged && ! $changed) {
                continue;
            }

            $row = [
                'tag_number' => $equipment['tag_number'],
                'category' => $equipment['category'],
                'criticality' => static::criticality($equipment['criticality'] ?? null),
                'sece' => static::sece($equipment['sece'] ?? null),
            ];

            foreach ($cells as $index => $cell) {
                $row['kondisi'.($index + 1)] = $cell['kondisi'];
                $row['status'.($index + 1)] = $cell['status'];
            }

            $rows[] = $row;
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
        if ($this->periodCount === 3) {
            return $this->periodHeadings([
                BusinessPeriod::previous(2)['code'],
                BusinessPeriod::previous(1)['code'],
                BusinessPeriod::current()['code'],
            ]);
        }

        return $this->periodHeadings([
            BusinessPeriod::previous(1)['code'],
            BusinessPeriod::current()['code'],
        ]);
    }

    private function periodHeadings(array $codes): array
    {
        $headings = [

            'No',

            'Tag Number',

            'Kategori Peralatan',

            'Criticality',

            'SECE',

        ];

        foreach ($codes as $code) {
            $headings[] = 'Kondisi Peralatan ('.static::periodLabel($code).')';
        }

        foreach ($codes as $code) {
            $headings[] = 'Status ('.static::periodLabel($code).')';
        }

        return $headings;
    }

    public function map($row): array
    {
        $columns = [

            ++$this->no,

            $row['tag_number'],

            $row['category'],

            $row['criticality'],

            $row['sece'],

        ];

        for ($index = 1; $index <= $this->periodCount; $index++) {
            $columns[] = $row['kondisi'.$index];
        }

        for ($index = 1; $index <= $this->periodCount; $index++) {
            $columns[] = $row['status'.$index];
        }

        return $columns;
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

                $lastColumn = $this->periodCount === 3 ? 'K' : 'I';

                $statusColumns = $this->periodCount === 3
                    ? ['I', 'J', 'K']
                    : ['H', 'I'];

                /**
                 * Freeze Header
                 */
                $sheet->freezePane('A2');

                /**
                 * Auto Filter
                 */
                $sheet->setAutoFilter('A1:'.$lastColumn.$last);

                /**
                 * Header Style
                 */
                $sheet->getStyle('A1:'.$lastColumn.'1')->applyFromArray([

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
                    $sheet->getStyle('A2:'.$lastColumn.$last)->applyFromArray([

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

                        foreach ($statusColumns as $column) {

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
                    ->getStyle('A:'.$lastColumn)
                    ->getAlignment()
                    ->setVertical(Alignment::VERTICAL_CENTER);

                $sheet
                    ->getStyle('A2:A'.$last)
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $sheet
                    ->getStyle($statusColumns[0].'2:'.$lastColumn.$last)
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER);

            },

        ];
    }
}
