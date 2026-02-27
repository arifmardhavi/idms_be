<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LaporanInspection extends BaseModel
{
    use HasFactory;

    protected $fillable = ['tag_number_id'];
    protected $appends = ['unit', 'type', 'category'];

    public function tagNumber()
    {
        return $this->belongsTo(Tag_number::class);
    }

    /* ======================
     | RELATIONS (WAJIB)
     ====================== */

    public function internalInspection()
    {
        return $this->hasMany(InternalInspection::class);
    }

    public function externalInspection()
    {
        return $this->hasMany(ExternalInspection::class);
    }

    public function breakdownReport()
    {
        return $this->hasMany(BreakdownReport::class);
    }

    public function surveillance()
    {
        return $this->hasMany(Surveillance::class);
    }

    public function overhaul()
    {
        return $this->hasMany(Overhaul::class);
    }

    public function preventive()
    {
        return $this->hasMany(Preventive::class);
    }

    public function onstream()
    {
        return $this->hasMany(OnstreamInspection::class);
    }

    /* ======================
     | ACCESSORS
     ====================== */

    public function getUnitAttribute()
    {
        return $this->tagNumber?->unit;
    }

    public function getTypeAttribute()
    {
        return $this->tagNumber?->type;
    }

    public function getCategoryAttribute()
    {
        return $this->tagNumber?->type?->category;
    }
}
