<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LaporanInspection extends BaseModel
{
    use HasFactory;
    protected $fillable = ['tag_number_id'];
    protected $appends = ['unit', 'type', 'category'];

    // Relation with Tag_number
    public function tagNumber(){
        return $this->belongsTo(Tag_number::class);
    }


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
