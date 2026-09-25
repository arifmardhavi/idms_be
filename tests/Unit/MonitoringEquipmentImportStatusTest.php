<?php

namespace Tests\Unit;

use App\Services\MonitoringEquipmentImportService;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class MonitoringEquipmentImportStatusTest extends TestCase
{
    private const KONDISI_STATUS = [
        'Terjadi Leak' => 'Breakdown',
        'Out Of Service / Breakdown' => 'Breakdown',
        'Tidak Ada Leak, Major Defect' => 'Low',
        'Tidak Ada Leak, Overdue >= 6 Bulan' => 'Low',
        'Tidak Ada Leak, Minor Defect' => 'Medium',
        'Tidak Ada Leak, Overdue < 6 Bulan' => 'Medium',
        'No Issue' => 'High',
    ];

    private function resolve($rawStatus, $kondisi): ?string
    {
        $method = new ReflectionMethod(
            MonitoringEquipmentImportService::class,
            'resolveStatus'
        );

        return $method->invoke(
            new MonitoringEquipmentImportService,
            $rawStatus,
            $kondisi,
            self::KONDISI_STATUS
        );
    }

    private function canonical($kondisi): ?string
    {
        $method = new ReflectionMethod(
            MonitoringEquipmentImportService::class,
            'canonicalKondisi'
        );

        return $method->invoke(
            new MonitoringEquipmentImportService,
            $kondisi,
            self::KONDISI_STATUS
        );
    }

    public function test_explicit_status_is_respected(): void
    {
        $this->assertSame('Medium', $this->resolve('Medium', 'Terjadi Leak'));
    }

    public function test_empty_status_derived_from_kondisi(): void
    {
        $this->assertSame('Breakdown', $this->resolve('', 'Terjadi Leak'));
        $this->assertSame('High', $this->resolve(null, 'No Issue'));
    }

    public function test_formula_string_status_derived_from_kondisi(): void
    {
        $formula = '=IFERROR(VLOOKUP(B2,Reference!$A$8:$B$14,2,FALSE),"")';

        $this->assertSame('Low', $this->resolve($formula, 'Tidak Ada Leak, Major Defect'));
        $this->assertSame('Medium', $this->resolve($formula, 'Tidak Ada Leak, Minor Defect'));
    }

    public function test_unknown_kondisi_yields_null(): void
    {
        $this->assertNull($this->resolve('', 'Kondisi Tidak Dikenal'));
    }

    public function test_empty_kondisi_yields_null(): void
    {
        $this->assertNull($this->resolve('', null));
        $this->assertNull($this->resolve('=IFERROR(VLOOKUP(B2,Reference!$A$8:$B$14,2,FALSE),"")', ''));
    }

    public function test_lowercase_kondisi_maps_to_status(): void
    {
        $this->assertSame('Breakdown', $this->resolve('', 'Out of Service / Breakdown'));
    }

    public function test_mixed_case_kondisi_maps_to_status(): void
    {
        $this->assertSame('Breakdown', $this->resolve('', 'out OF service / breakdown'));
        $this->assertSame('High', $this->resolve('', '  no issue  '));
    }

    public function test_canonical_kondisi_uses_db_spelling(): void
    {
        $this->assertSame('Out Of Service / Breakdown', $this->canonical('Out of Service / Breakdown'));
        $this->assertSame('No Issue', $this->canonical('  No Issue  '));
    }

    public function test_canonical_kondisi_keeps_unknown_value(): void
    {
        $this->assertSame('Kondisi Baru / X', $this->canonical('Kondisi Baru / X'));
        $this->assertNull($this->canonical(''));
    }
}
