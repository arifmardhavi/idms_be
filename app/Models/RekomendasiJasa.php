<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RekomendasiJasa extends BaseModel
{
    use HasFactory;
    protected $fillable = ['readiness_jasa_id', 'historical_memorandum_id', 'rekomendasi_file', 'target_date', 'status'];

    public function readiness_jasa()
    {
        return $this->belongsTo(ReadinessJasa::class);
    }

    public function historical_memorandum()
    {
        return $this->belongsTo(HistoricalMemorandum::class);
    }
}
