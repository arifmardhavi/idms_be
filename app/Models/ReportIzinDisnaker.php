<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReportIzinDisnaker extends Model
{
    use HasFactory;
    protected $fillable = ["izin_disnaker_id", 'report_izin_disnaker'];

    public function izinDisnaker()
    {
        return $this->belongsTo(IzinDisnaker::class);
    }
}
