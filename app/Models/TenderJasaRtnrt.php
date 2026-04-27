<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TenderJasaRtnrt extends Model
{
    use HasFactory;
    protected $fillable = ['readiness_jasa_rtnrt_id','description', 'target_date', 'status'];

    public function readiness_jasa_rtnrt()
    {
        return $this->belongsTo(ReadinessJasaRtnrt::class, 'readiness_jasa_rtnrt_id');
    }
}
