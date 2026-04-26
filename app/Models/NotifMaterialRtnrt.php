<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotifMaterialRtnrt extends BaseModel
{
    use HasFactory;
    protected $fillable = ['readiness_material_rtnrt_id','no_notif', 'target_date', 'status'];

    public function readiness_material_rtnrt()
    {
        return $this->belongsTo(ReadinessMaterialRtnrt::class, 'readiness_material_rtnrt_id');
    }
}
