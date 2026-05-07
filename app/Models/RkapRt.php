<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RkapRt extends Model
{
    use HasFactory;
    protected $fillable = [
        'judul',
    ];

    protected $appends = ['total'];

    protected $with = ['detailRkapRt']; // auto eager load

    public function detailRkapRt()
    {
        return $this->hasMany(DetailRkapRt::class, 'rkap_rt_id');
    }

    // helper total actual per row (optional, tapi berguna)
    public function getTotalActualAttribute()
    {
        return $this->detailRkapRt->sum('actual');
    }
}
