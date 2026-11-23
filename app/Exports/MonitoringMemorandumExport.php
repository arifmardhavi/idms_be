<?php

namespace App\Exports;

use App\Support\TagNumberIds;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

class MonitoringMemorandumExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping, WithTitle
{
    private int $no = 0;

    public function __construct(protected Builder $builder)
    {
    }

    public function title(): string
    {
        return 'Monitoring Memorandum';
    }

    public function query(): Builder
    {
        return $this->builder;
    }

    public function headings(): array
    {
        return [
            'No',
            'Tanggal Issue',
            'Issue Operasional',
            'Tag Number',
            'Nota Dinas',
            'No Notif',
            'Status Notif',
            'No WO',
            'Status WO',
            'No PO',
            'Status',
            'Backlog Days',
            'Keterangan',
        ];
    }

    public function map($row): array
    {
        return [
            ++$this->no,
            $row->tanggal_issue?->format('Y-m-d') ?? '',
            $row->issue_operasional,
            TagNumberIds::display($row->tag_number_ids),
            $row->historicalMemorandum?->no_dokumen ?? '',
            $row->no_notif ?? '',
            $row->status_notif ?? '',
            $row->no_wo ?? '',
            $row->status_wo ?? '',
            $row->no_po ?? '',
            $row->status ?? '',
            $row->backlog_days,
            $row->keterangan ?? '',
        ];
    }
}
