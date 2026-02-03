<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MdrResource extends JsonResource
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
            'engineering_data_id' => $this->engineering_data_id,
            'folder_name' => $this->folder_name,
            'files' => $this->whenLoaded('mdrItems'),
        ];
    }
}
