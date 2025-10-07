<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotifJasa extends BaseModel
{
    use HasFactory;
    protected $fillable = ['readiness_jasa_id','no_notif', 'target_date', 'status'];

    public function readiness_jasas()
    {
        return $this->belongsTo(ReadinessJasa::class, 'readiness_jasa_id');
    }
}
