<?php

namespace Tests\Unit;

use App\Support\BacklogDays;
use PHPUnit\Framework\TestCase;

class BacklogDaysBucketTest extends TestCase
{
    /**
     * @dataProvider bucketBoundaryProvider
     */
    public function test_bucket_uses_twenty_day_ranges(int $days, int $expected): void
    {
        $this->assertSame($expected, BacklogDays::bucket($days));
    }

    public static function bucketBoundaryProvider(): array
    {
        return [
            'zero' => [0, 1],
            'first range start' => [1, 1],
            'first range end' => [20, 1],
            'second range start' => [21, 2],
            'second range end' => [40, 2],
            'third range start' => [41, 3],
            'third range end' => [60, 3],
            'last range start' => [61, 4],
            'far past' => [207, 4],
        ];
    }

    public function test_first_bucket_meta_starts_at_zero(): void
    {
        $this->assertSame(
            [
                'bucket' => '0–20',
                'hari_min' => 0,
                'hari_max' => 20,
            ],
            BacklogDays::meta(1)
        );
    }
}
