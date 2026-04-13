<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Feature extends BaseModel
{
    use HasFactory;
    protected $fillable = [
        'feature',
        'group',
    ];


}
