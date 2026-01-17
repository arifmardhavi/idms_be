<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SertifikatKalibrasiResource extends JsonResource
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
            'no_sertifikat_kalibrasi' => $this->no_sertifikat_kalibrasi,
            'issue_date' => $this->issue_date,
            'overdue_date' => $this->overdue_date,
            'file_sertifikat_kalibrasi' => $this->file_sertifikat_kalibrasi,
            'file_old_sertifikat_kalibrasi' => $this->file_old_sertifikat_kalibrasi,
            'due_days' => $this->due_days,
            'type_id' => $this->type_id,
            'category_id' => $this->category_id,
            'unit_id' => $this->unit_id,
            'tag_number' => $this->whenLoaded('tag_number'),
        ];
    }
}
