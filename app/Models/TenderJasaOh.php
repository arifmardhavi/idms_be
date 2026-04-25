<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TenderJasaOh extends BaseModel
{
    use HasFactory;
    protected $fillable = ['readiness_jasa_oh_id','description', 'target_date', 'status'];

    public function readiness_jasa_oh()
    {
        return $this->belongsTo(ReadinessJasaOh::class, 'readiness_jasa_oh_id');
    }
}
