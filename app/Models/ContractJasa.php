<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContractJasa extends BaseModel
{
    use HasFactory;
    protected $fillable = ['readiness_jasa_id', 'contract_new_id', 'status'];

    public function readiness_jasa()
    {
        return $this->belongsTo(ReadinessJasa::class, 'readiness_jasa_id');
    }

    public function contract_new()
    {
        return $this->belongsTo(ContractNew::class, 'contract_new_id');
    }
}
