<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetailRkapRt extends BaseModel
{
    use HasFactory;
    protected $fillable = [
        'rkap_rt_id',
        'periode',
        'plan',
        'actual',
    ];

    protected $casts = [
        'periode' => 'integer',
        'plan' => 'integer',
        'actual' => 'integer',
    ];

    public function rkapRt()
    {
        return $this->belongsTo(RkapRt::class, 'rkap_rt_id');
    }
}
