<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RekomendasiMaterialOh extends BaseModel
{
    use HasFactory;
    protected $fillable = ['readiness_material_oh_id', 'historical_memorandum_id', 'rekomendasi_file', 'target_date', 'status'];

    public function readiness_material_oh()
    {
        return $this->belongsTo(ReadinessMaterialOh::class, 'readiness_material_oh_id');
    }

    public function historical_memorandum()
    {
        return $this->belongsTo(HistoricalMemorandum::class, 'historical_memorandum_id');
    }
}
