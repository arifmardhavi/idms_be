<?php

namespace App\Services;

use App\Models\MonitoringPsv;
use App\Support\MasaBerlakuBucket;
use App\Support\PsvTagNumber;

/**
 * Ringkasan kondisi Monitoring PSV.
 *
 * Dua query:
 *   1. headline  — total, bucket masa berlaku, PSV vs TSV, rekap status redundant
 *   2. by_kategori — cross tab kategori x bucket
 *
 * Berbeda dari MonitoringEquipmentDashboardService yang memakai satu
 * selectRaw raksasa, dua query terpisah dipakai karena Monitoring PSV hanya
 * butuh dua agregat dan bentuknya jauh lebih mudah dibaca.
 *
 * Dashboard bersifat global: tidak menerima filter, sama seperti
 * MonitoringEquipmentDashboardService.
 */
class MonitoringPsvDashboardService
{
    private const DATE_COLUMN = 'cois.overdue_date';

    private const TAG_COLUMN = 'tag_numbers.tag_number';

    public function getDashboard(): array
    {
        return [
            'summary' => $this->summary(),
            'by_kategori' => $this->byKategori(),
            'status_redundant' => $this->statusRedundant(),
        ];
    }

    /**
     * Kartu utama: total, bucket masa berlaku, PSV vs TSV.
     */
    private function summary(): array
    {
        $row = MonitoringPsv::query()
            ->psvTsv()
            ->selectRaw('COUNT(*) as total')
            ->selectRaw(
                MasaBerlakuBucket::countSql(self::DATE_COLUMN, MasaBerlakuBucket::SAFE) . ' as safe'
            )
            ->selectRaw(
                MasaBerlakuBucket::countSql(self::DATE_COLUMN, MasaBerlakuBucket::WARNING) . ' as warning'
            )
            ->selectRaw(
                MasaBerlakuBucket::countSql(self::DATE_COLUMN, MasaBerlakuBucket::EXPIRED) . ' as expired'
            )
            ->selectRaw(
                'SUM(CASE WHEN '
                . PsvTagNumber::conditionSql(self::TAG_COLUMN, PsvTagNumber::MARKERS[0])
                . ' THEN 1 ELSE 0 END) as psv'
            )
            ->selectRaw(
                'SUM(CASE WHEN '
                . PsvTagNumber::conditionSql(self::TAG_COLUMN, PsvTagNumber::MARKERS[1])
                . ' THEN 1 ELSE 0 END) as tsv'
            )
            ->first();

        return [
            'total'   => (int) ($row->total ?? 0),
            'safe'    => (int) ($row->safe ?? 0),
            'warning' => (int) ($row->warning ?? 0),
            'expired' => (int) ($row->expired ?? 0),
            'psv'     => (int) ($row->psv ?? 0),
            'tsv'     => (int) ($row->tsv ?? 0),
        ];
    }

    /**
     * Cross tab kategori x bucket.
     * Kategori NULL (belum diisi user) selalu diurutkan terakhir.
     */
    private function byKategori(): array
    {
        return MonitoringPsv::query()
            ->psvTsv()
            ->select('monitoring_psv.kategori')
            ->selectRaw('COUNT(*) as total')
            ->selectRaw(
                MasaBerlakuBucket::countSql(self::DATE_COLUMN, MasaBerlakuBucket::SAFE) . ' as safe'
            )
            ->selectRaw(
                MasaBerlakuBucket::countSql(self::DATE_COLUMN, MasaBerlakuBucket::WARNING) . ' as warning'
            )
            ->selectRaw(
                MasaBerlakuBucket::countSql(self::DATE_COLUMN, MasaBerlakuBucket::EXPIRED) . ' as expired'
            )
            ->groupBy('monitoring_psv.kategori')
            ->orderByRaw('monitoring_psv.kategori IS NULL')
            ->orderBy('monitoring_psv.kategori')
            ->get()
            ->map(fn ($row) => [
                'kategori' => $row->kategori,
                'total'    => (int) $row->total,
                'safe'     => (int) $row->safe,
                'warning'  => (int) $row->warning,
                'expired'  => (int) $row->expired,
            ])
            ->all();
    }

    /**
     * Rekap kolom status_redundant: sudah diisi atau belum.
     *
     * Perbandingan di bawah WAJIB memakai tulisan persis dari
     * `MonitoringPsv::STATUS_REDUNDANT_OPTIONS` — nilai yang sama dengan
     * hasil normalisasi `UpdateMonitoringPsvRequest`. Input HTTP tidak lagi
     * bisa menyelinap dengan bentuk lain (mis. `redundant` atau `Redundant `
     * dengan spasi), jadi penjumlahan `redundant + not_redundant + unfilled`
     * selalu sama dengan `total`.
     */
    private function statusRedundant(): array
    {
        $redundant    = MonitoringPsv::STATUS_REDUNDANT_OPTIONS['redundant'];
        $notRedundant = MonitoringPsv::STATUS_REDUNDANT_OPTIONS['not_redundant'];

        $row = MonitoringPsv::query()
            ->psvTsv()
            ->selectRaw('COUNT(*) as total')
            ->selectRaw(
                "SUM(CASE WHEN monitoring_psv.status_redundant = '{$redundant}' THEN 1 ELSE 0 END) as redundant"
            )
            ->selectRaw(
                "SUM(CASE WHEN monitoring_psv.status_redundant = '{$notRedundant}' THEN 1 ELSE 0 END) as not_redundant"
            )
            ->selectRaw(
                'SUM(CASE WHEN monitoring_psv.status_redundant IS NULL THEN 1 ELSE 0 END) as unfilled'
            )
            ->first();

        return [
            'redundant'     => (int) ($row->redundant ?? 0),
            'not_redundant' => (int) ($row->not_redundant ?? 0),
            'unfilled'      => (int) ($row->unfilled ?? 0),
            'total'         => (int) ($row->total ?? 0),
        ];
    }
}
