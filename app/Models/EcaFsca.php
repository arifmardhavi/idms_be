<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EcaFsca extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'unit_id',
        'functional_location',
        'tanggal',
        'eca_fsca_file',
    ];

    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($ecaFsca) {
            if ($ecaFsca->eca_fsca_file) {
                $filePath = public_path('eca_fsca/' . $ecaFsca->eca_fsca_file);
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
            }
        });
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }
}
