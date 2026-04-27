<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RekomendasiMaterialRtnrt extends BaseModel
{
    use HasFactory;
    protected $fillable = ['readiness_material_rtnrt_id', 'historical_memorandum_id', 'rekomendasi_file', 'target_date', 'status'];

    public function readiness_material_rtnrt()
    {
        return $this->belongsTo(ReadinessMaterialRtnrt::class, 'readiness_material_rtnrt_id');
    }

    public function historical_memorandum()
    {
        return $this->belongsTo(HistoricalMemorandum::class, 'historical_memorandum_id');
    }
}
