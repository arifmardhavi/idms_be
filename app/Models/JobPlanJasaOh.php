<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobPlanJasaOh extends BaseModel
{
    use HasFactory;
    protected $fillable = ['readiness_jasa_oh_id','no_wo', 'kak_file', 'boq_file', 'durasi_preparation','target_date', 'status'];
    public function readiness_jasa_oh()
    {
        return $this->belongsTo(ReadinessJasaOh::class, 'readiness_jasa_oh_id');
    }
}
