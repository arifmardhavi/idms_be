<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TerminResource extends JsonResource
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
            'termin' => $this->termin,
            'description' => $this->description,
            'receipt_nominal' => $this->receipt_nominal,
            'receipt_file' => $this->receipt_file,
        ];
    }
}
