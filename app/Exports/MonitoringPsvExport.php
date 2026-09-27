<?php

namespace App\Exports;

use App\Support\MasaBerlakuBucket;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

/**
 * Export Monitoring PSV.
 *
 * Query sudah dibangun oleh controller (baseQuery yang sama persis dengan
 * endpoint list), jadi isi export dijamin identik dengan hasil filter di layar.
 */
class MonitoringPsvExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping, WithTitle
{
    public function __construct(protected Builder $builder)
    {
    }

    public function title(): string
    {
        return 'Monitoring PSV';
    }

    public function query(): Builder
    {
        return $this->builder;
    }

    public function headings(): array
    {
        return [
            'No',
            'Tag Number',
            'Masa Berlaku COI',
            'Sisa Hari',
            'Status Masa Berlaku',
            'Status Redundant',
            'PID No',
            'Kategori',
            'Keterangan',
        ];
    }

    public function map($row): array
    {
        $sisaHari = $row->sisa_hari;

        return [

            $row->id,

            $row->tag_number,

            $this->date($row->overdue_date),

            $sisaHari ?? '-',

            $this->bucket($sisaHari),

            $row->status_redundant ?? '-',

            $row->pid_no ?? '-',

            $row->kategori ?? '-',

            $row->keterangan ?? '-',

        ];
    }

    private function date($value): string
    {
        if (empty($value)) {
            return '-';
        }

        return Carbon::parse($value)->format('Y-m-d');
    }

    private function bucket(?int $days): string
    {
        return match (MasaBerlakuBucket::forDays($days)) {
            MasaBerlakuBucket::SAFE    => 'Safe',
            MasaBerlakuBucket::WARNING => 'Warning',
            default                   => 'Expired',
        };
    }
}
