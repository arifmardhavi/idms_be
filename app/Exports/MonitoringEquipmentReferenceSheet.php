<?php

namespace App\Exports;

use App\Models\KondisiPeralatan;
use App\Models\StatusPeralatan;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class MonitoringEquipmentReferenceSheet implements FromArray, ShouldAutoSize, WithEvents, WithTitle
{
    private static ?array $statusValues = null;

    private static ?array $kondisiMap = null;

    public function title(): string
    {
        return 'Reference';
    }

    public static function statusValues(): array
    {
        return self::$statusValues ??= StatusPeralatan::query()
            ->where('is_active', 1)
            ->orderBy('id')
            ->pluck('status_peralatan')
            ->all();
    }

    public static function kondisiMap(): array
    {
        return self::$kondisiMap ??= KondisiPeralatan::query()
            ->where('is_active', 1)
            ->orderBy('id')
            ->pluck('status', 'kondisi_peralatan')
            ->all();
    }

    public static function ranges(): array
    {
        $statusEnd = 1 + count(static::statusValues());
        $kondisiStart = $statusEnd + 2;
        $kondisiEnd = $kondisiStart + count(static::kondisiMap()) - 1;

        return [
            'status' => 'Reference!$B$2:$B$'.$statusEnd,
            'kondisi' => 'Reference!$A$'.$kondisiStart.':$A$'.$kondisiEnd,
            'lookup' => 'Reference!$A$'.$kondisiStart.':$B$'.$kondisiEnd,
        ];
    }

    public function array(): array
    {
        $rows = [
            ['Status', 'Description'],
        ];

        foreach (static::statusValues() as $index => $status) {
            $rows[] = [(string) $index, $status];
        }

        $rows[] = ['Kondisi Peralatan', 'Status'];

        foreach (static::kondisiMap() as $kondisi => $status) {
            $rows[] = [$kondisi, $status];
        }

        return $rows;
    }

    public function registerEvents(): array
    {
        return [

            AfterSheet::class => function (AfterSheet $event) {

                $sheet = $event->sheet->getDelegate();

                $kondisiHeader = 'A'.(count(static::statusValues()) + 2);

                foreach (['A1:C1', $kondisiHeader.':'.$kondisiHeader] as $range) {
                    $sheet->getStyle($range)->getFont()->setBold(true);

                    $sheet->getStyle($range)
                        ->getFill()
                        ->setFillType(Fill::FILL_SOLID)
                        ->getStartColor()
                        ->setRGB('D97706');
                }

                $sheet->getProtection()->setSheet(true);

                $sheet->getProtection()->setPassword('idms');

            },

        ];
    }
}
