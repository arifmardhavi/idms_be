<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Gms extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'title',
        'tanggal',
        'gms_file',
    ];

    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($gms) {
            if ($gms->gms_file) {
                $filePath = public_path('gms/' . $gms->gms_file);
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
            }
        });
    }
}
