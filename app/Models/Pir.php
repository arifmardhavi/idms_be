<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pir extends BaseModel
{
    use HasFactory;
    protected $fillable = [
        'judul',
        'tanggal_pir',
        'pir_file',
    ];
}
