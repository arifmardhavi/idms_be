<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GaDrawing extends Model
{
    use HasFactory;
    protected $table = 'ga_drawings';
    protected $fillable = [
        'nama_dokumen',
        'no_dokumen',
        'engineering_data_id',
        'drawing_file',
        'date_drawing', 
    ];
    public function engineeringData()
    {
        return $this->belongsTo(EngineeringData::class);
    }
}
