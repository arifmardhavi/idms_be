<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MonitoringPipeline extends BaseModel
{
    use HasFactory;

    protected $table = 'monitoring_pipeline';

    protected $fillable = [

        'tag_number_id',

        'tml_drawing_number',

        'api_service_class',

        'insulation',

        'grade_material',

        'nps',

        'od',

        'sch',

        't_nom',

        'operating_press',

        'design_press',

        'design_temp',

        's',

        'e',

        'w',

        'y',

        'min_req_thk',

        'half_nom_thk',

        'tanggal_pasang_line',

        'actual_thk',

        'tanggal_ut_terakhir',

        'corrosion_rate',

        'remaining_life',

        'alert_structural_thickness',

        'next_plan_inspection_api',

        'used_plan_inspection',

        'pipe_condition',

        'estimated_end_of_life',

        'maintenance_status',

        'rencana_tindak_lanjut',

    ];

    protected $casts = [

        'nps' => 'float',
        'od' => 'float',
        't_nom' => 'float',
        'operating_press' => 'float',
        'design_press' => 'float',
        'design_temp' => 'float',
        's' => 'float',
        'e' => 'float',
        'w' => 'float',
        'y' => 'float',
        'min_req_thk' => 'float',
        'half_nom_thk' => 'float',
        'actual_thk' => 'float',
        'corrosion_rate' => 'float',
        'remaining_life' => 'float',
        'alert_structural_thickness' => 'float',

        'tanggal_pasang_line' => 'date:Y-m-d',
        'tanggal_ut_terakhir' => 'date:Y-m-d',
        'next_plan_inspection_api' => 'date:Y-m-d',
        'used_plan_inspection' => 'date:Y-m-d',
        'estimated_end_of_life' => 'date:Y-m-d',

    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function tagNumber()
    {
        return $this->belongsTo(
            Tag_number::class,
            'tag_number_id'
        );
    }

    public function getRecordLabelAttribute()
    {
        return $this->tagNumber?->tag_number;
    }
}
