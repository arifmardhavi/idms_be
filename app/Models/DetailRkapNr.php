<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetailRkapNr extends BaseModel
{
    use HasFactory;
    protected $fillable = [
        'rkap_nr_id',
        'periode',
        'plan',
        'actual',
    ];

    protected $casts = [
        'periode' => 'integer',
        'plan' => 'integer',
        'actual' => 'integer',
    ];

    public function rkapNr()
    {
        return $this->belongsTo(RkapNr::class, 'rkap_nr_id');
    }
}
