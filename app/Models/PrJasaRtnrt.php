<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PrJasaRtnrt extends BaseModel
{
    use HasFactory;
    protected $fillable = ['readiness_jasa_rtnrt_id','no_pr', 'target_date', 'status'];

    public function readiness_jasa_rtnrt()
    {
        return $this->belongsTo(ReadinessJasaRtnrt::class, 'readiness_jasa_rtnrt_id');
    }
}
