<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MonitoringEquipmentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tag_number_id' => $this->tag_number_id,
            'tag_number' => $this->tagNumber?->tag_number,
            'criticality' => $this->criticality,
            'sece' => (int) $this->sece,
            'status' => (int) $this->status,
            'tindak_lanjut' => $this->tindak_lanjut,
            'target' => $this->target,
        ];
    }
}
