<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MdrFolder extends Model
{
    use HasFactory;
    protected $fillable = [
        'engineering_data_id',
        'folder_name',
    ];

    public function engineeringData()
    {
        return $this->belongsTo(EngineeringData::class);
    }

    public function mdrItems()
    {
        return $this->hasMany(MdrItem::class);
    }

}
