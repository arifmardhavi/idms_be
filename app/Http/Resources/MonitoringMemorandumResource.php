<?php

namespace App\Http\Resources;

use App\Support\TagNumberIds;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MonitoringMemorandumResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'tanggal_issue'     => $this->tanggal_issue?->format('Y-m-d'),
            'issue_operasional' => $this->issue_operasional,
            'tag_number_ids'    => $this->tag_number_ids,
            'is_all_tag_number' => $this->is_all_tag_number,
            'tag_numbers'       => TagNumberIds::resolve($this->tag_number_ids),
            'nota_dinas'        => $this->nota_dinas,
            'no_dokumen'        => $this->whenLoaded(
                'historicalMemorandum',
                fn () => $this->historicalMemorandum?->no_dokumen
            ),
            'no_notif'          => $this->no_notif,
            'status_notif'      => $this->status_notif,
            'no_wo'             => $this->no_wo,
            'status_wo'         => $this->status_wo,
            'no_po'             => $this->no_po,
            'status'            => $this->status,
            'backlog_days'      => $this->backlog_days,
            'keterangan'        => $this->keterangan,
            'created_at'        => $this->created_at?->toJSON(),
            'updated_at'        => $this->updated_at?->toJSON(),
        ];
    }
}
