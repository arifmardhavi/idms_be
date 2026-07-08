<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MonitoringEquipmentResource extends JsonResource
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

            'criticality' => (int) $this->criticality,

            'sece' => (int) $this->sece,

            'status' => (int) $this->status,

            'jenis_kerusakan' => $this->jenis_kerusakan,

            'penyebab' => $this->penyebab,

            'penanganan_sementara' => $this->penanganan_sementara,

            'perbaikan_permanen' => $this->perbaikan_permanen,

            'progress_perbaikan_permanen' => $this->progress_perbaikan_permanen,

            'kendala_perbaikan' => $this->kendala_perbaikan,

            'estimasi_perbaikan' => $this->estimasi_perbaikan,

            'target' => $this->target ?? null,

            'created_at' => optional($this->created_at)
                ->format('Y-m-d H:i:s'),

            'updated_at' => optional($this->updated_at)
                ->format('Y-m-d H:i:s'),

            'logs' => MonitoringEquipmentLogResource::collection(
                $this->whenLoaded('logs')
            ),

        ];
    }
}