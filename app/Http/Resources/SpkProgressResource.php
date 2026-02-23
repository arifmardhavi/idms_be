<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SpkProgressResource extends JsonResource
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
            'spk_new_id' => $this->spk_new_id,
            'week' => $this->week,
            'plan' => $this->plan,
            'actual' => $this->actual,
            'progress_file' => $this->progress_file,
            'spk_name' => $this->spk_name
        ];
    }
}
