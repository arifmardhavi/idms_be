<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PrMaterial extends BaseModel
{
    use HasFactory;
    protected $fillable = ['readiness_material_id','no_pr', 'target_date', 'status'];
    
    public function readiness_material()
    {
        return $this->belongsTo(ReadinessMaterial::class);
    }
}
