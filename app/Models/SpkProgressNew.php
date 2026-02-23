<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SpkProgressNew extends Model
{
    use HasFactory;
    protected $fillable = [
        'spk_new_id',
        'week',
        'plan',
        'actual',
        'progress_file',
    ];

    protected $appends = ['spk_name'];

    public function spkNew()
    {
        return $this->belongsTo(SpkNew::class);
    }
   
    public function getSpkNameAttribute()
    {
        return $this->spkNew ? $this->spkNew->spk_name : null;
    }

}
