<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RkapOh extends BaseModel
{
    use HasFactory;
    protected $fillable = [
        'judul',
    ];

    protected $appends = ['total'];

    protected $with = ['detailRkapOh']; // auto eager load

    public function detailRkapOh()
    {
        return $this->hasMany(DetailRkapOh::class, 'rkap_oh_id');
    }

    // helper total actual per row (optional, tapi berguna)
    public function getTotalActualAttribute()
    {
        return $this->detailRkapOh->sum('actual');
    }
}
