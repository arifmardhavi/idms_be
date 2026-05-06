<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RkapTa extends BaseModel
{
    use HasFactory;
    protected $fillable = [
        'judul',
    ];

    protected $appends = ['total'];

    protected $with = ['detailRkapTa']; // auto eager load

    public function detailRkapTa()
    {
        return $this->hasMany(DetailRkapTa::class, 'rkap_ta_id');
    }

    // helper total actual per row (optional, tapi berguna)
    public function getTotalActualAttribute()
    {
        return $this->detailRkapTa->sum('actual');
    }
}
