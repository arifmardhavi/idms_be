<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SpkResource extends JsonResource
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
            'contract_new_id' => $this->contract_new_id,
            'no_spk' => $this->no_spk,
            'spk_name' => $this->spk_name,
            'spk_start_date' => $this->spk_start_date,
            'spk_end_date' => $this->spk_end_date,
            'spk_price' => $this->spk_price,
            'spk_file' => $this->spk_file,
            'spk_status' => $this->spk_status,
            'receipt_nominal' => $this->receipt_nominal,
            'receipt_file' => $this->receipt_file,
            'total_weeks' => $this->total_weeks,
        ];
    }
}
