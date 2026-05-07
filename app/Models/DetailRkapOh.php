<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetailRkapOh extends BaseModel
{
    use HasFactory;
    protected $fillable = [
        'rkap_oh_id',
        'periode',
        'plan',
        'actual',
    ];

    protected $casts = [
        'periode' => 'integer',
        'plan' => 'integer',
        'actual' => 'integer',
    ];

    public function rkapOh()
    {
        return $this->belongsTo(RkapOh::class, 'rkap_oh_id');
    }
}
