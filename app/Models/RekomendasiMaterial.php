<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RekomendasiMaterial extends BaseModel
{
    use HasFactory;
    protected $fillable = ['readiness_material_id', 'historical_memorandum_id', 'rekomendasi_file', 'target_date', 'status'];

    public function readiness_material()
    {
        return $this->belongsTo(ReadinessMaterial::class, 'readiness_material_id');
    }

    public function historical_memorandum()
    {
        return $this->belongsTo(HistoricalMemorandum::class, 'historical_memorandum_id');
    }

}
