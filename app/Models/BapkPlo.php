<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BapkPlo extends BaseModel
{
    use HasFactory;
    protected $fillable = ["plo_id", 'bapk_plo'];

    protected $appends = ['unit_name'];
    protected $hidden = ['plo'];
    
    public function plo()
    {
        return $this->belongsTo(Plo::class);
    }

    public function getUnitNameAttribute()
    {
        return $this->plo ? $this->plo->unit->unit_name : null;
    }
}
