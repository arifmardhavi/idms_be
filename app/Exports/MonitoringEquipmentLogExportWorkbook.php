<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class MonitoringEquipmentLogExportWorkbook implements WithMultipleSheets
{
    protected array $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    /**
     * Generate Excel Sheets
     */
    public function sheets(): array
    {
        return [

            /**
             * Sheet 1
             * Monitoring Equipment Logs Data
             */
            new MonitoringEquipmentLogExport($this->filters),

            /**
             * Sheet 2
             * Reference Dropdown
             */
            new MonitoringEquipmentReferenceSheet,

        ];
    }
}
