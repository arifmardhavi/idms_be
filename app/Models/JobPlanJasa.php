<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobPlanJasa extends BaseModel
{
    use HasFactory;
    protected $fillable = ['readiness_jasa_id','no_wo', 'kak_file', 'boq_file', 'durasi_preparation','target_date', 'status'];
    public function readiness_jasa()
    {
        return $this->belongsTo(ReadinessJasa::class);
    }
}
