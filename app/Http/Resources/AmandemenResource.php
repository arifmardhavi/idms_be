<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AmandemenResource extends JsonResource
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
            'contract_price_before_amandemen' => $this->contract_price_before_amandemen,
            'ba_agreement_file' => $this->ba_agreement_file,
            'result_amandemen_file' => $this->result_amandemen_file,
            'principle_permit_file' => $this->principle_permit_file,
            'amandemen_price' => $this->amandemen_price,
            'amandemen_end_date' => $this->amandemen_end_date,
            'amandemen_penalty' => $this->amandemen_penalty,
            'amandemen_termin' => $this->amandemen_termin,
            'contract_price_before_amandemen' => $this->contract_price_before_amandemen,
        ];
    }
}
