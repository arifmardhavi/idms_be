<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RkapOhResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $details = $this->detailRkapOh->keyBy('periode');

        $dataPeriode = collect(range(1, 12))->map(function ($periode) use ($details) {

            $plan = $details[$periode]->plan ?? 0;
            $actual = $details[$periode]->actual ?? 0;

            return [
                'periode' => $periode,
                'plan' => $plan,
                'actual' => $actual,
                'selisih' => $plan - $actual,
                'total' => $actual
            ];
        });

        return [
            'id' => $this->id,
            'judul' => $this->judul,
            'data_periode' => $dataPeriode->values(),
        ];
    }
}
