<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotifJasaRtnrt extends BaseModel
{
    use HasFactory;
    protected $fillable = ['readiness_jasa_rtnrt_id','no_notif', 'target_date', 'status'];

    public function readiness_jasa_rtnrt()
    {
        return $this->belongsTo(ReadinessJasaRtnrt::class);
    }
}
