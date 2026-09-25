<?php

namespace Tests\Unit;

use App\Exports\MonitoringEquipmentChangeSheet;
use PHPUnit\Framework\TestCase;

class MonitoringEquipmentChangeSheetTest extends TestCase
{
    private function equipments(): array
    {
        return [
            ['tag_number_id' => 1, 'tag_number' => 'T-001', 'category' => 'Pompa', 'criticality' => 0, 'sece' => 1],
            ['tag_number_id' => 2, 'tag_number' => 'T-002', 'category' => 'Kompresor', 'criticality' => 1, 'sece' => 0],
            ['tag_number_id' => 3, 'tag_number' => 'T-003', 'category' => 'Kipas', 'criticality' => 2, 'sece' => 0],
            ['tag_number_id' => 4, 'tag_number' => 'T-004', 'category' => 'Valve', 'criticality' => 3, 'sece' => 0],
        ];
    }

    private function prevLogs(): array
    {
        return [
            1 => ['kondisi' => 'No Issue', 'status' => 'High'],
            2 => ['kondisi' => 'Terjadi Leak', 'status' => 'Breakdown'],
            4 => ['kondisi' => 'No Issue', 'status' => 'High'],
        ];
    }

    private function currLogs(): array
    {
        return [
            1 => ['kondisi' => 'No Issue', 'status' => 'High'],
            2 => ['kondisi' => 'No Issue', 'status' => 'High'],
            3 => ['kondisi' => 'No Issue', 'status' => 'High'],
            4 => ['kondisi' => 'No Issue', 'status' => 'Medium'],
        ];
    }

    private function rows(): array
    {
        return MonitoringEquipmentChangeSheet::buildRows(
            $this->equipments(),
            $this->prevLogs(),
            $this->currLogs()
        );
    }

    public function test_unchanged_equipment_is_excluded(): void
    {
        $result = $this->rows();

        $tagNumbers = array_column($result, 'tag_number');

        $this->assertNotContains('T-001', $tagNumbers);
    }

    public function test_kondisi_and_status_change_is_included(): void
    {
        $result = $this->rows();

        $rows = array_values(array_filter(
            $result,
            fn ($row) => $row['tag_number'] === 'T-002'
        ));

        $this->assertCount(1, $rows);
        $this->assertSame('Terjadi Leak', $rows[0]['kondisi_prev']);
        $this->assertSame('No Issue', $rows[0]['kondisi_curr']);
        $this->assertSame('Breakdown', $rows[0]['status_prev']);
        $this->assertSame('High', $rows[0]['status_curr']);
        $this->assertSame('Medium High', $rows[0]['criticality']);
        $this->assertSame('Tidak', $rows[0]['sece']);
    }

    public function test_status_only_change_is_included(): void
    {
        $result = $this->rows();

        $rows = array_values(array_filter(
            $result,
            fn ($row) => $row['tag_number'] === 'T-004'
        ));

        $this->assertCount(1, $rows);
        $this->assertSame('High', $rows[0]['status_prev']);
        $this->assertSame('Medium', $rows[0]['status_curr']);
    }

    public function test_missing_previous_log_is_treated_as_change(): void
    {
        $result = $this->rows();

        $rows = array_values(array_filter(
            $result,
            fn ($row) => $row['tag_number'] === 'T-003'
        ));

        $this->assertCount(1, $rows);
        $this->assertSame('-', $rows[0]['kondisi_prev']);
        $this->assertSame('-', $rows[0]['status_prev']);
        $this->assertSame('No Issue', $rows[0]['kondisi_curr']);
    }

    public function test_null_status_without_change_is_excluded(): void
    {
        $result = MonitoringEquipmentChangeSheet::buildRows(
            [
                ['tag_number_id' => 9, 'tag_number' => 'T-009', 'category' => null, 'criticality' => null, 'sece' => null],
            ],
            [9 => ['kondisi' => null, 'status' => null]],
            [9 => ['kondisi' => null, 'status' => null]]
        );

        $this->assertSame([], $result);
    }
}
