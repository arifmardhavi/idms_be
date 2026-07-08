<?php

namespace App\Helpers;

use Carbon\Carbon;

class BusinessPeriod
{
    /**
     * Mendapatkan periode bisnis aktif.
     *
     * Contoh:
     * Hari ini : 8 Juli
     * Start    : 26 Juni
     * End      : 25 Juli
     * Code     : 2026-07
     */
    public static function current(): array
    {
        $today = now();

        if ($today->day >= 26) {

            $start = Carbon::create(
                $today->year,
                $today->month,
                26
            );

        } else {

            $previousMonth = $today->copy()->subMonth();

            $start = Carbon::create(
                $previousMonth->year,
                $previousMonth->month,
                26
            );

        }

        $end = $start
            ->copy()
            ->addMonth()
            ->subDay();

        return [
            'code' => $end->format('Y-m'),
            'start' => $start,
            'end' => $end,
        ];
    }

    /**
     * previous(0) = current
     * previous(1) = bulan sebelumnya
     * previous(2) = dua bulan sebelumnya
     */
    public static function previous(int $month = 0): array
    {
        $current = self::current();

        $start = Carbon::parse($current['start'])
            ->subMonths($month);

        $end = $start
            ->copy()
            ->addMonth()
            ->subDay();

        return [
            'code' => $end->format('Y-m'),
            'start' => $start,
            'end' => $end,
        ];
    }

    /**
     * Period yang masih boleh disimpan.
     */
    public static function allowedPeriods(): array
    {
        return [
            self::previous(0)['code'],
            self::previous(1)['code'],
            self::previous(2)['code'],
        ];
    }
}