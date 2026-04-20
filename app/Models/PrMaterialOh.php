<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PrMaterialOh extends Model
{
    use HasFactory;
    protected $fillable = ['readiness_material_oh_id','no_pr', 'target_date', 'status'];

    public function readiness_material_oh()
    {
        return $this->belongsTo(ReadinessMaterialOh::class, 'readiness_material_oh_id');
    }
}
