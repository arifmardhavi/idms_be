<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MdrItem extends Model
{
    use HasFactory;
    protected $fillable = [
        'mdr_folder_id',
        'file_name',
    ];
    public function mdrFolder()
    {
        return $this->belongsTo(MdrFolder::class);
    }
    
}
