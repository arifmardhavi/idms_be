<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContractJasaOh extends BaseModel
{
    use HasFactory;
    protected $fillable = ['readiness_jasa_oh_id', 'contract_new_id', 'status'];

    public function readiness_jasa_oh()
    {
        return $this->belongsTo(ReadinessJasaOh::class, 'readiness_jasa_oh_id');
    }

    public function contract_new()
    {
        return $this->belongsTo(ContractNew::class, 'contract_new_id');
    }
}
