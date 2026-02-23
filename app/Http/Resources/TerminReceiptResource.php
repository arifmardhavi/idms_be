<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TerminReceiptResource extends JsonResource
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
            'termin_new_id' => $this->termin_new_id,
            'receipt_nominal' => $this->receipt_nominal,
            'receipt_file' => $this->receipt_file,
            'termin' => $this->termin, // Menambahkan atribut termin
        ];
    }
}
