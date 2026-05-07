<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class RkapRtCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'success' => true,
            'message' => 'RKAP retrieved successfully.',
            'data' => RkapRtResource::collection($this->collection),
        ];
    }

    public function paginationInformation($request, $paginated, $default)
    {
        return [
            'meta' => [
                'current_page' => $paginated['current_page'],
                'last_page' => $paginated['last_page'],
                'per_page' => $paginated['per_page'],
                'total' => $paginated['total'],
            ]
        ];
    }
}
