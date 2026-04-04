<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
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
            'fullname' => $this->fullname,
            'email' => $this->email,
            'username' => $this->username,
            'level_user' => $this->level_user,
            'status' => $this->status,
            'contract_news' => $this->contract_news->pluck('id')->toArray(), // hanya ambil ID dari kontrak
            'total_file_open' => $this->total_file_open,
            'file_open_per_feature' => $this->file_open_per_feature,
            'total_activities' => $this->total_activities,
            'activities_per_feature' => $this->activities_per_feature,
        ];  
    }
}
