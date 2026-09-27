<?php

namespace App\Http\Resources;

use App\Support\MasaBerlakuBucket;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

class MonitoringPsvResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        /**
         * Berasal dari scope withCoiData(). Null kalau scope tidak dipakai.
         */
        $sisaHari = $this->sisa_hari;

        return [

            'id' => $this->id,

            'coi_id' => $this->coi_id,

            /* ============================================================
                DARI COI
            ============================================================ */

            'tag_number' => $this->tag_number,

            'masa_berlaku' => optional($this->overdue_date)->format('Y-m-d'),

            'sisa_hari' => $sisaHari,

            'status_masa_berlaku' => MasaBerlakuBucket::forDays($sisaHari),

            /* ============================================================
                INPUTAN MANUAL
            ============================================================ */

            'status_redundant' => $this->status_redundant,

            'pid_no' => $this->pid_no,

            'kategori' => $this->kategori,

            'keterangan' => $this->keterangan,

            /* ============================================================
                DETAIL COI (hanya pada endpoint show)

                PENTING: semua field di sini dibaca dari RELASI $this->coi,
                bukan dari atribut hasil SELECT. scopeWithCoiData() sengaja
                tidak memilih no_certificate / issue_date / coi_certificate
                lagi karena tidak dipakai kolom manapun. Mengubah baris ini
                jadi $this->issue_date dst. akan balik diam-diam jadi null
                tanpa error — karena Eloquent tidak tahu kolom tidak di-select.
            ============================================================ */

            'coi' => $this->whenLoaded('coi', fn () => [
                'id'             => $this->coi->id,
                'no_certificate' => $this->coi->no_certificate,
                'issue_date'     => $this->formatDate($this->coi->issue_date),
                'overdue_date'   => $this->formatDate($this->coi->overdue_date),
                'coi_certificate' => $this->coi->coi_certificate,
            ]),

            /* ============================================================
                TIMESTAMP
            ============================================================ */

            'created_at' => $this->formatDateTime($this->created_at),

            'updated_at' => $this->formatDateTime($this->updated_at),

        ];
    }

    /**
     * Model Coi TIDAK punya date casts, jadi issue_date / overdue_date
     * arrives sebagai string mentah. optional($string)->format() akan
     * mengembalikan null diam-diam karena Optional::__call hanya meneruskan
     * ke object — jadi parsing dilakukan eksplisit di sini.
     */
    private function formatDate($value): ?string
    {
        if (empty($value)) {
            return null;
        }

        return Carbon::parse($value)->format('Y-m-d');
    }

    private function formatDateTime($value): ?string
    {
        if (empty($value)) {
            return null;
        }

        return Carbon::parse($value)->format('Y-m-d H:i:s');
    }
}
