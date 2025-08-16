<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tag_number extends BaseModel
{
    use HasFactory;
    protected $fillable = ['unit_id','type_id' , 'tag_number', 'description', 'status'];

    public function type()
    {
        return $this->belongsTo(Type::class);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }
}
