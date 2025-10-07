<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContractJasa extends BaseModel
{
    use HasFactory;
    protected $fillable = ['readiness_jasa_id', 'contract_id', 'status'];

    public function readiness_jasa()
    {
        return $this->belongsTo(ReadinessJasa::class, 'readiness_jasa_id');
    }

    public function contract()
    {
        return $this->belongsTo(Contract::class, 'contract_id');
    }
}
