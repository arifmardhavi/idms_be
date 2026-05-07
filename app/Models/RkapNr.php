<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RkapNr extends BaseModel
{
    use HasFactory;
    protected $fillable = [
        'judul',
    ];

    protected $appends = ['total'];

    protected $with = ['detailRkapNr']; // auto eager load

    public function detailRkapNr()
    {
        return $this->hasMany(DetailRkapNr::class, 'rkap_nr_id');
    }

    // helper total actual per row (optional, tapi berguna)
    public function getTotalActualAttribute()
    {
        return $this->detailRkapNr->sum('actual');
    }
}
