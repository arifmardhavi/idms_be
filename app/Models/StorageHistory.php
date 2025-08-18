<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StorageHistory extends Model
{
    use HasFactory;
    protected $fillable = [
        'total_size_mb',
        'total_size_gb',
    ];
}
