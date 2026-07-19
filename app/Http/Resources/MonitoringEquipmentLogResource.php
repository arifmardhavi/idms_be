<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MonitoringEquipmentLogResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [

            'id' => $this->id,

            'tag_number_id' => $this->tag_number_id,

            'tag_number' => optional($this->tagNumber)->tag_number,

            'criticality' => $this->tagNumber->criticality ?? null,

            'sece' => $this->tagNumber->sece ?? null,

            'kondisi_peralatan' => $this->kondisi_peralatan,

            'status' => $this->status,

            'jenis_kerusakan' => $this->jenis_kerusakan,

            'penyebab' => $this->penyebab,

            'penanganan_sementara' => $this->penanganan_sementara,

            'perbaikan_permanen' => $this->perbaikan_permanen,

            'progress_perbaikan_permanen' => $this->progress_perbaikan_permanen,

            'kendala_perbaikan' => $this->kendala_perbaikan,

            'estimasi_perbaikan' => $this->estimasi_perbaikan,

            'target' => $this->target ?? null,

            'period_code' => $this->period_code,

            'period_start' => optional($this->period_start)
                ->format('Y-m-d'),

            'period_end' => optional($this->period_end)
                ->format('Y-m-d'),

            'created_at' => optional($this->created_at)
                ->format('Y-m-d H:i:s'),

        ];
    }
}