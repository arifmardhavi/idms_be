<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobPlanJasaRtnrt extends BaseModel
{
    use HasFactory;
    protected $fillable = ['readiness_jasa_rtnrt_id','no_wo', 'kak_file', 'boq_file', 'durasi_preparation','target_date', 'status'];
    public function readiness_jasa_rtnrt()
    {
        return $this->belongsTo(ReadinessJasaRtnrt::class, 'readiness_jasa_rtnrt_id');
    }
}
