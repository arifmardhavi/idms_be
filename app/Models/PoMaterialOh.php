<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PoMaterialOh extends Model
{
    use HasFactory;
    protected $fillable = ['readiness_material_oh_id', 'contract_new_id','no_po', 'po_file', 'delivery_date', 'status'];

    public function readiness_material_oh()
    {
        return $this->belongsTo(ReadinessMaterialOh::class, 'readiness_material_oh_id');
    }

    public function contract_new()
    {
        return $this->belongsTo(ContractNew::class, 'contract_new_id');
    }


}
