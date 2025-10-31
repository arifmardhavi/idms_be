<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class P_id extends BaseModel
{
    use HasFactory;
    protected $fillable = [
        'file_name',
        'p_id_file',
    ];
}
