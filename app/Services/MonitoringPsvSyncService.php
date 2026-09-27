<?php

namespace App\Services;

use App\Models\Coi;
use App\Models\MonitoringPsv;
use App\Support\PsvTagNumber;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Membuat baris Monitoring PSV dari COI PSV/TSV.
 *
 * Dua pemanggil:
 *   1. Listener Coi::created   -> syncFromCoi()  (COI baru)
 *   2. POST /monitoring_psv/sync -> syncAll()   (backfill / repair)
 *
 * Keduanya idempotent sehingga aman dipanggil berulang.
 * TIDAK pernah menghapus baris: baris hilang otomatis lewat cascade delete
 * dari cois, dan query list sudah memfilter ulang aturan PSV/TSV.
 */
class MonitoringPsvSyncService
{
    /**
     * Baris untuk satu COI. No-op kalau tag number-nya bukan PSV/TSV.
     */
    public function syncFromCoi(Coi $coi): ?MonitoringPsv
    {
        if (! PsvTagNumber::matches($coi->tag_number?->tag_number)) {
            return null;
        }

        $existing = MonitoringPsv::where('coi_id', $coi->getKey())->first();

        return $existing ?? $this->createForCoi($coi->getKey());
    }

    /**
     * Backfill / repair: pastikan setiap COI PSV/TSV punya baris monitoring.
     *
     * @return array{scanned:int, created:int, skipped:int}
     */
    public function syncAll(): array
    {
        /**
         * Satu transaksi supaya tidak pernah ada keadaan setengah sinkron
         * kalau gagal di tengah jalan.
         */
        return DB::transaction(function () {

            $coiIds = $this->psvTsvCoiQuery()
                ->orderBy('cois.id')
                ->pluck('cois.id')
                ->all();

            /**
             * Set coi_id yang sudah punya baris monitoring.
             */
            $existing = array_flip(
                MonitoringPsv::whereIn('coi_id', $coiIds)
                    ->pluck('coi_id')
                    ->all()
            );

            $scanned = 0;
            $created = 0;

            foreach ($coiIds as $coiId) {

                $scanned++;

                if (isset($existing[$coiId])) {
                    continue;
                }

                $this->createForCoi($coiId);
                $created++;
            }

            return [
                'scanned' => $scanned,
                'created' => $created,
                'skipped' => $scanned - $created,
            ];

        });
    }

    /**
     * coi_id sengaja TIDAK ada di $fillable (kolom sistem, bukan input user),
     * jadi atributnya di-set langsung — bukan lewat mass assignment.
     */
    private function createForCoi(int $coiId): MonitoringPsv
    {
        $monitoringPsv = new MonitoringPsv;
        $monitoringPsv->coi_id = $coiId;
        $monitoringPsv->save();

        return $monitoringPsv;
    }

    /**
     * Semua COI yang tag number-nya PSV/TSV.
     */
    private function psvTsvCoiQuery(): Builder
    {
        $query = Coi::query()
            ->join('tag_numbers', 'tag_numbers.id', '=', 'cois.tag_number_id')
            ->select('cois.*');

        return PsvTagNumber::applyToQuery($query, 'tag_numbers.tag_number');
    }
}
