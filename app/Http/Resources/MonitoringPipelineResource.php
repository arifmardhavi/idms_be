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
            'design_press_pa' => is_null($this->design_press) ? null : round((float) $this->design_press * 98066.5, 2),

            'design_temp' => $this->design_temp,

            's' => $this->s,
            's_pa' => is_null($this->s) ? null : round((float) $this->s * 1000000, 2),

            'e' => $this->e,

            'w' => $this->w,

            'y' => $this->y,

            'min_req_thk' => is_null($this->min_req_thk) ? null : round((float) $this->min_req_thk, 2),

            'half_nom_thk' => is_null($this->half_nom_thk) ? null : round((float) $this->half_nom_thk, 2),

            'tanggal_pasang_line' => optional($this->tanggal_pasang_line)
                ->format('Y-m-d'),

            'actual_thk' => is_null($this->actual_thk) ? null : round((float) $this->actual_thk, 2),

            'tanggal_ut_terakhir' => optional($this->tanggal_ut_terakhir)
                ->format('Y-m-d'),

            'corrosion_rate' => is_null($this->corrosion_rate) ? null : round((float) $this->corrosion_rate, 2),

            'remaining_life' => is_null($this->remaining_life) ? null : round((float) $this->remaining_life, 2),

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
