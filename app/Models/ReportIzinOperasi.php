<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReportIzinOperasi extends Model
{
    use HasFactory;

    protected $fillable = ["izin_operasi_id", 'report_izin_operasi'];
    
    public function izin_operasi()
    {
        return $this->belongsTo(IzinOperasi::class);
    }
}
