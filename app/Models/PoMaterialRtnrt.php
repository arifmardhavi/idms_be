<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PoMaterialRtnrt extends Model
{
    use HasFactory;
    protected $fillable = ['readiness_material_rtnrt_id', 'contract_new_id','no_po', 'delivery_date', 'status'];

    public function readiness_material_rtnrt()
    {
        return $this->belongsTo(ReadinessMaterialRtnrt::class, 'readiness_material_rtnrt_id');
    }

    public function contract_new()
    {
        return $this->belongsTo(ContractNew::class, 'contract_new_id');
    }
}
