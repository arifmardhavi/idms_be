<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TenderJasa extends BaseModel
{
    use HasFactory;
    protected $fillable = ['readiness_jasa_id','description', 'target_date', 'status'];

    public function readiness_jasa()
    {
        return $this->belongsTo(ReadinessJasa::class, 'readiness_jasa_id');
    }
}
