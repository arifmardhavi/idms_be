<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContractJasa extends BaseModel
{
    use HasFactory;
    protected $fillable = ['readiness_jasa_id', 'contract_file', 'status'];

    public function readiness_jasa()
    {
        return $this->belongsTo(ReadinessJasa::class);
    }
}
