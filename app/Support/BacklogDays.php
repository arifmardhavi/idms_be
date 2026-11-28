<?php

namespace App\Support;

use Carbon\Carbon;
use Carbon\CarbonInterface;

class BacklogDays
{
    public static function daysSql(string $dateColumn): string
    {
        return "DATEDIFF(CURDATE(), {$dateColumn})";
    }

    public static function forDate(CarbonInterface|string|null $date): int
    {
        if ($date === null) {
            return 0;
        }

        // Positif untuk tanggal lampau; tanggal_issue divalidasi <= hari ini.
        return Carbon::parse($date)->diffInDays(Carbon::today(), false);
    }

    public static function bucket(int $days): int
    {
        return min(intdiv(max($days - 1, 0), 20) + 1, 4);
    }

    public static function label(int $bucket): string
    {
        return match ($bucket) {
            1 => '0–20',
            2 => '21–40',
            3 => '41–60',
            default => '61+',
        };
    }

    /** @return array{bucket:string, hari_min:int, hari_max:?int} */
    public static function meta(int $bucket): array
    {
        return match ($bucket) {
            1 => ['bucket' => self::label(1), 'hari_min' => 0, 'hari_max' => 20],
            2 => ['bucket' => self::label(2), 'hari_min' => 21, 'hari_max' => 40],
            3 => ['bucket' => self::label(3), 'hari_min' => 41, 'hari_max' => 60],
            default => ['bucket' => self::label(4), 'hari_min' => 61, 'hari_max' => null],
        };
    }
}
