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

    protected $appends = [
        'feature_name',
        'group_name',
    ];

    public function feature()
    {
        return $this->belongsTo(Feature::class);
    }

    public function getFeatureNameAttribute()
    {
        return $this->feature ? $this->feature?->feature : null;
    }

    public function getGroupNameAttribute()
    {
        return $this->feature ? $this->feature?->group : null;
    }
    
}
