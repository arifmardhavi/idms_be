<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Nib extends Model
{
    use HasFactory;
    protected $fillable = [
        'no_nib',
        'judul',
        'tanggal_nib',
        'nib_file',
    ];
}
