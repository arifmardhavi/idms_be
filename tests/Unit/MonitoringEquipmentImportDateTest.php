<?php

namespace Tests\Unit;

use App\Services\MonitoringEquipmentImportService;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class MonitoringEquipmentImportDateTest extends TestCase
{
    private function normalize($value): ?string
    {
        $method = new ReflectionMethod(
            MonitoringEquipmentImportService::class,
            'normalizeTargetDate'
        );

        return $method->invoke(
            new MonitoringEquipmentImportService,
            $value
        );
    }

    public function test_excel_serial_integer_converted_to_iso_date(): void
    {
        $this->assertSame('2026-12-01', $this->normalize(46357));
    }

    public function test_excel_serial_float_converted_to_iso_date(): void
    {
        $this->assertSame('2026-04-01', $this->normalize(46113.0));
    }

    public function test_text_iso_date_is_normalized(): void
    {
        $this->assertSame('2026-08-06', $this->normalize('2026-08-6'));
    }

    public function test_slash_date_string_is_normalized(): void
    {
        $this->assertSame('2026-12-01', $this->normalize('12/1/2026'));
    }

    public function test_empty_values_become_null(): void
    {
        $this->assertNull($this->normalize(null));
        $this->assertNull($this->normalize(''));
        $this->assertNull($this->normalize(0));
    }

    public function test_unparseable_value_become_null(): void
    {
        $this->assertNull($this->normalize('not-a-date'));
    }
}
