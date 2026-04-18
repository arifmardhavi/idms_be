<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReadinessMaterialOh extends Model
{
    use HasFactory;
    protected $fillable = [
        'event_readiness_oh_id', 
        'material_name', 
        'price_estimate', 
        'type', 
        'current_status', 
        'tanggal_target', 
        'status'
    ];

    public function event_readiness_oh()
    {
        return $this->belongsTo(EventReadinessOh::class);
    }
}
