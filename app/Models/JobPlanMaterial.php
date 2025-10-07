<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobPlanMaterial extends BaseModel
{
    use HasFactory;
    protected $fillable = ['readiness_material_id','no_wo', 'kak_file', 'boq_file', 'target_date', 'status'];
    public function readiness_material()
    {
        return $this->belongsTo(ReadinessMaterial::class, 'readiness_material_id');
    }
}
