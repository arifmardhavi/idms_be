<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContractResource extends JsonResource
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
            'no_vendor' => $this->no_vendor,
            'vendor_name' => $this->vendor_name,
            'no_contract' => $this->no_contract,
            'contract_name' => $this->contract_name,
            'contract_type' => $this->contract_type,
            'contract_date' => $this->contract_date,
            'contract_price' => $this->contract_price,
            'contract_file' => $this->contract_file,
            'contract_start_date' => $this->contract_start_date,
            'contract_end_date' => $this->contract_end_date,
            'meeting_notes' => $this->meeting_notes,
            'contract_status' => $this->contract_status,
            'pengawas' => $this->pengawas,
            'current_status' => $this->current_status,
            'durasi_mpp' => $this->durasi_mpp,
            'sisa_nilai' => $this->sisa_nilai,
            'plan_progress' => $this->plan_progress,
            'actual_progress' => $this->actual_progress,
            'deviation_progress' => $this->deviation_progress,
        ];
    }
}
