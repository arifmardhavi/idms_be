<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PoMaterial extends BaseModel
{
    use HasFactory;
    protected $fillable = ['readiness_material_id', 'contract_id','no_po', 'po_file', 'delivery_date', 'target_date', 'status'];

    public function readiness_material()
    {
        return $this->belongsTo(ReadinessMaterial::class, 'readiness_material_id');
    }

    public function contract()
    {
        return $this->belongsTo(Contract::class, 'contract_id');
    }
}
