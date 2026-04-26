<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReadinessMaterialRtnrt extends Model
{
    use HasFactory;
    protected $fillable = [
        'event_readiness_rtnrt_id',
        'material_name',
        'price_estimate',
        'type',
        'current_status',
        'tanggal_target',
        'status'
    ];
}
