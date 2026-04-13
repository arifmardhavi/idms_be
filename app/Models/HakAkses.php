<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HakAkses extends BaseModel
{
    use HasFactory;
    protected $fillable = [
        'feature_id',
        'hak_akses',
    ];

    public function feature()
    {
        return $this->belongsTo(Feature::class);
    }
    
}
