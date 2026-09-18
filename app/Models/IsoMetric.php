<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class IsoMetric extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'no_drawing',
        'judul',
        'tanggal',
        'iso_metric_file',
    ];

    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($isoMetric) {
            if ($isoMetric->iso_metric_file) {
                $filePath = public_path('iso_metric/' . $isoMetric->iso_metric_file);
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
            }
        });
    }
}