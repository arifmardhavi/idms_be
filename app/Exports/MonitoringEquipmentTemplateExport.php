<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class MonitoringEquipmentTemplateExport implements WithMultipleSheets
{
    /**
     * Generate Excel Sheets
     */
    public function sheets(): array
    {
        return [

            /**
             * Sheet 1
             * Input Monitoring Equipment
             */
            new MonitoringEquipmentTemplateSheet(),

            /**
             * Sheet 2
             * Reference Dropdown
             */
            new MonitoringEquipmentReferenceSheet(),

        ];
    }
}