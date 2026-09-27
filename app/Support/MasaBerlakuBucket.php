<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;

/**
 * Bucket "Masa Berlaku" untuk Monitoring PSV.
 *
 *   sisa_hari > 270  (> 9 bulan lagi)   -> safe
 *   sisa_hari 1..270 (<= 9 bulan lagi)  -> warning
 *   sisa_hari <= 0   (lewat / hari ini) -> expired
 *
 * Batas bawah memakai `<= 0` sehingga tag yang jatuh tempo HARI INI sudah
 * berstatus expired. Perhatikan ini berbeda 1 baris dari filter lama di
 * CoiController yang memakai `DATEDIFF(...) < 0`.
 *
 * `overdue_date` NULL juga dihitung expired (bukan warning): COI tanpa tanggal
 * masa berlaku adalah gap kepatuhan, bukan kondisi aman. Baris seperti ini
 * tidak ada di data saat ini, tapi aturan ini menjaga agar filter, resource,
 * dan dashboard tidak pernah berbeda jawaban.
 *
 * Definisi ini dipakai oleh query list, filter, resource, export, dan
 * dashboard supaya batas bucket tidak pernah melenceng di salah satu tempat.
 */
class MasaBerlakuBucket
{
    /**
     * Ambang batas atas: sisa hari lebih dari ini = safe.
     */
    public const SAFE_DAYS = 270;

    public const SAFE = 'safe';

    public const WARNING = 'warning';

    public const EXPIRED = 'expired';

    /**
     * Semua nilai bucket yang valid (untuk filter query param).
     */
    public const ALL = [self::SAFE, self::WARNING, self::EXPIRED];

    /**
     * Bucket dari jumlah sisa hari.
     * Sisa hari negatif = sudah lewat. null = tanggal tidak tersedia.
     */
    public static function forDays(?int $days): string
    {
        if ($days === null || $days <= 0) {
            return self::EXPIRED;
        }

        if ($days > self::SAFE_DAYS) {
            return self::SAFE;
        }

        return self::WARNING;
    }

    /**
     * Ekspresi SQL jumlah sisa hari untuk sebuah kolom tanggal.
     */
    public static function daysSql(string $dateColumn): string
    {
        return "DATEDIFF({$dateColumn}, CURDATE())";
    }

    /**
     * Ekspresi SQL boolean (kondisi) untuk satu bucket.
     *
     * Dipakai oleh filter() maupun SUM(CASE WHEN ... ) di dashboard.
     *
     * Kolom tanggal NULL dihitung sebagai EXPIRED, sejalan dengan
     * forDays(null). Kalau tidak, tiga tempat akan berbeda jawaban:
     * resource menampilkan "expired", filter expired tidak memunculkan baris,
     * dan sql() mengklasifikasinya "warning".
     */
    public static function conditionSql(string $dateColumn, string $bucket): string
    {
        $days = self::daysSql($dateColumn);

        return match ($bucket) {
            self::SAFE    => "{$days} > " . self::SAFE_DAYS,
            self::WARNING => "{$days} BETWEEN 1 AND " . self::SAFE_DAYS,
            self::EXPIRED => "({$dateColumn} IS NULL OR {$days} <= 0)",
            default       => '1 = 0',
        };
    }

    /**
     * Ekspresi SQL yang mengembalikan nilai bucket sebagai string.
     *
     * Contoh: MasaBerlakuBucket::sql('cois.overdue_date')
     */
    public static function sql(string $dateColumn): string
    {
        return "CASE"
            . " WHEN " . self::conditionSql($dateColumn, self::SAFE) . " THEN '" . self::SAFE . "'"
            . " WHEN " . self::conditionSql($dateColumn, self::EXPIRED) . " THEN '" . self::EXPIRED . "'"
            . " ELSE '" . self::WARNING . "'"
            . " END";
    }

    /**
     * Agregat jumlah baris per bucket, untuk query ringkasan/dashboard.
     */
    public static function countSql(string $dateColumn, string $bucket): string
    {
        return "SUM(CASE WHEN " . self::conditionSql($dateColumn, $bucket) . " THEN 1 ELSE 0 END)";
    }

    /**
     * Filter query berdasarkan bucket.
     * Nilai di luar MasaBerlakuBucket::ALL diabaikan (tidak difilter).
     */
    public static function filter(Builder $query, string $dateColumn, ?string $bucket): Builder
    {
        if ($bucket === null || $bucket === '' || ! in_array($bucket, self::ALL, true)) {
            return $query;
        }

        return $query->whereRaw(self::conditionSql($dateColumn, $bucket));
    }
}
