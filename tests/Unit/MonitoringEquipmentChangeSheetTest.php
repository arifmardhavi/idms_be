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
        $this->assertSame('Terjadi Leak', $rows[0]['kondisi1']);
        $this->assertSame('No Issue', $rows[0]['kondisi2']);
        $this->assertSame('Breakdown', $rows[0]['status1']);
        $this->assertSame('High', $rows[0]['status2']);
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
        $this->assertSame('High', $rows[0]['status1']);
        $this->assertSame('Medium', $rows[0]['status2']);
    }

    public function test_missing_previous_log_is_treated_as_change(): void
    {
        $result = $this->rows();

        $rows = array_values(array_filter(
            $result,
            fn ($row) => $row['tag_number'] === 'T-003'
        ));

        $this->assertCount(1, $rows);
        $this->assertSame('-', $rows[0]['kondisi1']);
        $this->assertSame('-', $rows[0]['status1']);
        $this->assertSame('No Issue', $rows[0]['kondisi2']);
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

    public function test_include_unchanged_returns_all_equipments(): void
    {
        $result = MonitoringEquipmentChangeSheet::buildRows(
            $this->equipments(),
            $this->prevLogs(),
            $this->currLogs(),
            true
        );

        $this->assertCount(4, $result);

        $tagNumbers = array_column($result, 'tag_number');

        $this->assertContains('T-001', $tagNumbers);
        $this->assertContains('T-002', $tagNumbers);
        $this->assertContains('T-003', $tagNumbers);
        $this->assertContains('T-004', $tagNumbers);
    }

    public function test_include_unchanged_keeps_values_of_unchanged_equipment(): void
    {
        $result = MonitoringEquipmentChangeSheet::buildRows(
            $this->equipments(),
            $this->prevLogs(),
            $this->currLogs(),
            true
        );

        $rows = array_values(array_filter(
            $result,
            fn ($row) => $row['tag_number'] === 'T-001'
        ));

        $this->assertCount(1, $rows);
        $this->assertSame('No Issue', $rows[0]['kondisi1']);
        $this->assertSame('No Issue', $rows[0]['kondisi2']);
        $this->assertSame('High', $rows[0]['status1']);
        $this->assertSame('High', $rows[0]['status2']);
    }

    private function threePeriodLogs(): array
    {
        return [
            [
                1 => ['kondisi' => 'No Issue', 'status' => 'High'],
                2 => ['kondisi' => 'Terjadi Leak', 'status' => 'Breakdown'],
            ],
            [
                1 => ['kondisi' => 'No Issue', 'status' => 'High'],
                2 => ['kondisi' => 'No Issue', 'status' => 'High'],
                3 => ['kondisi' => 'No Issue', 'status' => 'High'],
            ],
            [
                1 => ['kondisi' => 'No Issue', 'status' => 'High'],
                2 => ['kondisi' => 'No Issue', 'status' => 'High'],
                3 => ['kondisi' => 'No Issue', 'status' => 'High'],
                4 => ['kondisi' => 'No Issue', 'status' => 'Medium'],
            ],
        ];
    }

    public function test_three_periods_equal_all_excluded_without_include_unchanged(): void
    {
        $equipment = [
            ['tag_number_id' => 1, 'tag_number' => 'T-001', 'category' => 'Pompa', 'criticality' => 0, 'sece' => 1],
        ];

        $logs = [
            [1 => ['kondisi' => 'No Issue', 'status' => 'High']],
            [1 => ['kondisi' => 'No Issue', 'status' => 'High']],
            [1 => ['kondisi' => 'No Issue', 'status' => 'High']],
        ];

        $this->assertSame([], MonitoringEquipmentChangeSheet::buildRows3($equipment, $logs));
    }

    public function test_three_periods_include_unchanged_returns_all(): void
    {
        $result = MonitoringEquipmentChangeSheet::buildRows3(
            $this->equipments(),
            $this->threePeriodLogs(),
            true
        );

        $this->assertCount(4, $result);

        $rows = array_values(array_filter(
            $result,
            fn ($row) => $row['tag_number'] === 'T-001'
        ));

        $this->assertCount(1, $rows);
        $this->assertSame('No Issue', $rows[0]['kondisi1']);
        $this->assertSame('No Issue', $rows[0]['kondisi2']);
        $this->assertSame('No Issue', $rows[0]['kondisi3']);
        $this->assertSame('High', $rows[0]['status1']);
        $this->assertSame('High', $rows[0]['status2']);
        $this->assertSame('High', $rows[0]['status3']);
    }

    public function test_three_periods_change_detected(): void
    {
        $result = MonitoringEquipmentChangeSheet::buildRows3(
            $this->equipments(),
            $this->threePeriodLogs()
        );

        $rows = array_values(array_filter(
            $result,
            fn ($row) => $row['tag_number'] === 'T-004'
        ));

        $this->assertCount(1, $rows);
        $this->assertSame('-', $rows[0]['status1']);
        $this->assertSame('-', $rows[0]['status2']);
        $this->assertSame('Medium', $rows[0]['status3']);
    }

    public function test_three_periods_missing_log_shows_dash(): void
    {
        $result = MonitoringEquipmentChangeSheet::buildRows3(
            $this->equipments(),
            $this->threePeriodLogs(),
            true
        );

        $rows = array_values(array_filter(
            $result,
            fn ($row) => $row['tag_number'] === 'T-003'
        ));

        $this->assertCount(1, $rows);
        $this->assertSame('-', $rows[0]['kondisi1']);
        $this->assertSame('-', $rows[0]['status1']);
        $this->assertSame('No Issue', $rows[0]['kondisi2']);
    }
}
