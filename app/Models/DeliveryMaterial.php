<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeliveryMaterial extends BaseModel
{
    use HasFactory;
    protected $fillable = ['readiness_material_id','description', 'delivery_file', 'target_date', 'status'];

    public function readiness_material()
    {
        return $this->belongsTo(ReadinessMaterial::class);
    }
}
