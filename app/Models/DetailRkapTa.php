<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetailRkapTa extends BaseModel
{
    use HasFactory;
    protected $fillable = [
        'rkap_ta_id',
        'periode',
        'plan',
        'actual',
    ];

    protected $casts = [
        'periode' => 'integer',
        'plan' => 'integer',
        'actual' => 'integer',
    ];

    public function rkapTa()
    {
        return $this->belongsTo(RkapTa::class, 'rkap_ta_id');
    }
}
