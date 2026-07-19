<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KondisiPeralatan extends Model
{
    use HasFactory;

    protected $fillable = [
        'kondisi_peralatan',
        'status',
        'is_active',
    ];
}
