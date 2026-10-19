<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MonitoringPipelineResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [

            'id' => $this->id,

            'tag_number_id' => $this->tag_number_id,

            'tag_number' => optional($this->tagNumber)->tag_number,

            'tml_drawing_number' => $this->tml_drawing_number,

            'api_service_class' => $this->api_service_class,

            'insulation' => $this->insulation,

            'grade_material' => $this->grade_material,

            'nps' => $this->nps,

            'od' => $this->od,

            'sch' => $this->sch,

            't_nom' => $this->t_nom,

            'operating_press' => $this->operating_press,

            'design_press' => $this->design_press,

            'design_temp' => $this->design_temp,

            's' => $this->s,

            'e' => $this->e,

            'w' => $this->w,

            'y' => $this->y,

            'min_req_thk' => $this->min_req_thk,

            'half_nom_thk' => $this->half_nom_thk,

            'tanggal_pasang_line' => optional($this->tanggal_pasang_line)
                ->format('Y-m-d'),

            'actual_thk' => $this->actual_thk,

            'tanggal_ut_terakhir' => optional($this->tanggal_ut_terakhir)
                ->format('Y-m-d'),

            'corrosion_rate' => $this->corrosion_rate,

            'remaining_life' => $this->remaining_life,

            'alert_structural_thickness' => $this->alert_structural_thickness,

            'next_plan_inspection_api' => optional($this->next_plan_inspection_api)
                ->format('Y-m-d'),

            'used_plan_inspection' => optional($this->used_plan_inspection)
                ->format('Y-m-d'),

            'pipe_condition' => $this->pipe_condition,

            'estimated_end_of_life' => optional($this->estimated_end_of_life)
                ->format('Y-m-d'),

            'maintenance_status' => $this->maintenance_status,

            'rencana_tindak_lanjut' => $this->rencana_tindak_lanjut,

            'created_at' => optional($this->created_at)
                ->format('Y-m-d H:i:s'),

            'updated_at' => optional($this->updated_at)
                ->format('Y-m-d H:i:s'),

        ];
    }
}
