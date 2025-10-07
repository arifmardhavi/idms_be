<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotifMaterial extends BaseModel
{
    use HasFactory;

    protected $fillable = ['readiness_material_id','no_notif', 'target_date', 'status'];

    public function readiness_materials()
    {
        return $this->belongsTo(ReadinessMaterial::class, 'readiness_material_id');
    }
}
