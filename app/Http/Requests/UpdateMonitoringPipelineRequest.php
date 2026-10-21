<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class UpdateMonitoringPipelineRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [

            'tag_number_id' => [
                // hanya divalidasi bila dikirim — update boleh parsial
                // (sesuai desain; tag_number_id tidak boleh berpindah baris)
                'sometimes',
                'required',
                'integer',
                'exists:tag_numbers,id',
                Rule::unique('monitoring_pipeline')
                    ->ignore($this->route('monitoring_pipeline')),
            ],

            'tml_drawing_number' => 'nullable|string|max:150',
            'api_service_class' => 'nullable|integer',
            'insulation' => 'nullable|string|in:Yes,No',
            'grade_material' => 'nullable|string|max:150',

            'nps' => 'nullable|numeric',
            'od' => 'nullable|numeric',
            'sch' => 'nullable|string|max:50',
            't_nom' => 'nullable|numeric',
            'operating_press' => 'nullable|numeric',
            'design_press' => 'nullable|numeric',
            'design_temp' => 'nullable|numeric',
            's' => 'nullable|numeric',
            'e' => 'nullable|numeric',
            'w' => 'nullable|numeric',
            'y' => 'nullable|numeric',
            'min_req_thk' => 'nullable|numeric',
            'half_nom_thk' => 'nullable|numeric',
            'actual_thk' => 'nullable|numeric',
            'corrosion_rate' => 'nullable|numeric',
            'remaining_life' => 'nullable|numeric',
            'alert_structural_thickness' => 'nullable|numeric',

            'tanggal_pasang_line' => 'nullable|date',
            'tanggal_ut_terakhir' => 'nullable|date',
            'next_plan_inspection_api' => 'nullable|date',
            'used_plan_inspection' => 'nullable|date',
            'estimated_end_of_life' => 'nullable|date',

            'pipe_condition' => 'nullable|string|max:255',
            'maintenance_status' => 'nullable|string|max:255',
            'rencana_tindak_lanjut' => 'nullable|string|max:255',

        ];
    }
}
