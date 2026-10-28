<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;

/**
 * Aturan "apakah sebuah tag_number termasuk PSV / TSV".
 *
 * Tidak ada kolom psv/tsv di database. Satu-satunya penanda adalah pola di
 * teks tag_number, contoh: "1-PSV-177/00", "1-TSV-175S/00", "5-TSV-501/00".
 *
 * Sengaja TIDAK memakai `type_id` (types 116 = Pressure Safety Valve,
 * 133 = Temperature Safety Valve) karena keduanya tidak ekuivalen dengan
 * tag_number: 14 tag PSV tercatat "Breather Valve", dan 33 tag TSV
 * tercatat "Pressure Safety Valve".
 *
 * Kelas ini adalah satu-satunya definisi aturan tersebut. Dipakai bersama oleh
 * listener Coi::created, MonitoringPsvSyncService, query list, export, dan
 * dashboard supaya tidak ada logika pencocokan yang berbeda antar tempat.
 */
class PsvTagNumber
{
    /**
     * Substring yang menandai tag PSV / TSV.
     */
    public const MARKERS = ['PSV', 'TSV'];

    /**
     * Tag number yang dikecualikan dari monitoring PSV (tidak ikut disinkronisasi/ditampilkan).
     */
    public const EXCLUDED_TAG_NUMBERS = [
        '4-PSV-178',
        '4-PSV-177',
        '4-PSV-184',
        '4-PSV-171',
        '4-PSV-172',
        '4-PSV-173',
        '4-PSV-176',
    ];

    /**
     * Cek apakah tag number dikecualikan.
     */
    public static function isExcluded(?string $tagNumber): bool
    {
        if ($tagNumber === null || trim($tagNumber) === '') {
            return false;
        }

        $base = strtoupper(explode('/', trim($tagNumber))[0]);

        foreach (self::EXCLUDED_TAG_NUMBERS as $excluded) {
            if (strtoupper($excluded) === $base) {
                return true;
            }
        }

        return false;
    }

    /**
     * Pemeriksaan sisi PHP (dipakai listener & validasi sebelum query).
     */
    public static function matches(?string $tagNumber): bool
    {
        if ($tagNumber === null || trim($tagNumber) === '') {
            return false;
        }

        if (self::isExcluded($tagNumber)) {
            return false;
        }

        foreach (self::MARKERS as $marker) {
            if (stripos($tagNumber, $marker) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Query PSV/TSV pada tabel `tag_numbers` (INNER JOIN wajib supaya hasil
     * selalu berada di dalam tabel tag_numbers).
     *
     * Catatan: `like` mengikuti collation kolom, yang default-nya
     * case-insensitive di MySQL — konsisten dengan stripos() di atas.
     */
    public static function query(?string $table = null): Builder
    {
        $table = $table ?: 'tag_numbers';
        $column = $table . '.tag_number';

        $query = \App\Models\Tag_number::query()
            ->where(function (Builder $q) use ($column) {
                foreach (self::MARKERS as $marker) {
                    $q->orWhere($column, 'like', '%' . $marker . '%');
                }
            });

        return self::applyExclusions($query, $column);
    }

    /**
     * Terapkan aturan PSV/TSV ke query yang sudah punya alias ke tag_numbers.
     * Dipakai oleh scope psvTsv() pada model MonitoringPsv.
     */
    public static function applyToQuery(Builder $query, string $column = 'tag_numbers.tag_number'): Builder
    {
        $query = $query->where(function (Builder $q) use ($column) {
            foreach (self::MARKERS as $marker) {
                $q->orWhere($column, 'like', '%' . $marker . '%');
            }
        });

        return self::applyExclusions($query, $column);
    }

    /**
     * Kecualikan tag number tertentu dari query.
     */
    public static function applyExclusions(Builder $query, string $column = 'tag_numbers.tag_number'): Builder
    {
        if (empty(self::EXCLUDED_TAG_NUMBERS)) {
            return $query;
        }

        foreach (self::EXCLUDED_TAG_NUMBERS as $excluded) {
            $query->where($column, 'not like', $excluded . '/%')
                ->where($column, '!=', $excluded);
        }

        return $query;
    }

    /**
     * Ekspresi SQL boolean untuk satu marker (untuk agregat dashboard).
     */
    public static function conditionSql(string $column, string $marker): string
    {
        return $column . " LIKE '%" . $marker . "%'";
    }
}
