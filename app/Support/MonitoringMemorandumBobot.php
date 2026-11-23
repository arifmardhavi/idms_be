<?php

namespace App\Support;

/**
 * Aturan bobot Monitoring Memorandum. Sumber tunggal untuk query dashboard
 * maupun verifikasi manual. Total bobot maksimal 100.
 */
class MonitoringMemorandumBobot
{
    public static function scoreSql(string $table = 'monitoring_memorandum'): string
    {
        return '(CASE WHEN ' . $table . '.nota_dinas IS NOT NULL THEN 10 ELSE 0 END'
            . ' + CASE WHEN ' . $table . '.no_notif IS NOT NULL THEN 10 ELSE 0 END'
            . ' + CASE WHEN ' . $table . '.status_notif IS NOT NULL THEN 20 ELSE 0 END'
            . ' + CASE WHEN ' . $table . '.no_wo IS NOT NULL THEN 10 ELSE 0 END'
            . ' + CASE WHEN ' . $table . '.status_wo IS NOT NULL THEN 20 ELSE 0 END'
            . " + CASE WHEN (" . $table . ".no_po IS NOT NULL AND TRIM(" . $table . ".no_po) <> '') THEN 10 ELSE 0 END"
            . " + CASE WHEN " . $table . ".status = 'close' THEN 20 ELSE 0 END)";
    }

    /** @param array<string, mixed> $row */
    public static function score(array $row): int
    {
        $score = 0;
        $score += self::filled($row, 'nota_dinas') ? 10 : 0;
        $score += self::filled($row, 'no_notif') ? 10 : 0;
        $score += self::filled($row, 'status_notif') ? 20 : 0;
        $score += self::filled($row, 'no_wo') ? 10 : 0;
        $score += self::filled($row, 'status_wo') ? 20 : 0;
        $score += self::filled($row, 'no_po') ? 10 : 0;
        $score += (($row['status'] ?? null) === 'close') ? 20 : 0;

        return $score;
    }

    /** @param array<string, mixed> $row */
    private static function filled(array $row, string $key): bool
    {
        return array_key_exists($key, $row)
            && $row[$key] !== null
            && $row[$key] !== '';
    }
}
