<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IzinUsaha extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'no_izin_usaha',
        'judul',
        'tanggal_izin_usaha',
        'izin_usaha_file',
    ];
}
