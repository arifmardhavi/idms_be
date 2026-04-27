<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RekomendasiJasaRtnrt extends BaseModel
{
    use HasFactory;
    protected $fillable = ['readiness_jasa_rtnrt_id', 'historical_memorandum_id', 'rekomendasi_file', 'target_date', 'status'];

    public function readiness_jasa_rtnrt()
    {
        return $this->belongsTo(ReadinessJasaRtnrt::class, 'readiness_jasa_rtnrt_id');
    }

    public function historical_memorandum()
    {
        return $this->belongsTo(HistoricalMemorandum::class, 'historical_memorandum_id');
    }
}
