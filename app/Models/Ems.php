<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ems extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'tag_number_id',
        'no_ems',
        'tanggal',
        'file',
    ];

    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($ems) {
            if ($ems->file) {
                $filePath = public_path('ems/' . $ems->file);
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
            }
        });
    }

    public function tag_number()
    {
        return $this->belongsTo(Tag_number::class);
    }
}
