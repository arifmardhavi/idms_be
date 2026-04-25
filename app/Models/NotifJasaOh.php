<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotifJasaOh extends BaseModel
{
    use HasFactory;
    protected $fillable = ['readiness_jasa_oh_id','no_notif', 'target_date', 'status'];

    public function readiness_jasa_oh()
    {
        return $this->belongsTo(ReadinessJasaOh::class);
    }
}
