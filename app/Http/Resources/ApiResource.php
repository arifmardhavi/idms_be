<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApiResource
{
    public static function pagination($data, $resource)
    {
        return response()->json([
            'success' => true,
            'message' => 'Success',
            'data' => $resource::collection($data->getCollection()),
            'meta' => [
                'current_page' => $data->currentPage(),
                'last_page' => $data->lastPage(),
                'per_page' => $data->perPage(),
                'total' => $data->total(),
            ],
        ]);
    }

    public static function item($resource)
    {
        return response()->json([
            'success' => true,
            'message' => 'Success',
            'data' => $resource,
        ]);
    }
}