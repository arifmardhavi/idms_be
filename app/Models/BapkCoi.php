<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BapkCoi extends BaseModel
{
    use HasFactory;
    protected $fillable = ["coi_id", 'bapk_coi'];

    protected $appends = ['unit_name'];
    protected $hidden = ['coi'];
    
    public function coi()
    {
        return $this->belongsTo(Coi::class);
    }

    public function getUnitNameAttribute()
    {
        return $this->coi ? $this->coi->plo?->unit?->unit_name : null;
    }

}
