<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReportCoi extends Model
{
    use HasFactory;
    protected $fillable = ["coi_id", 'report_coi'];
    
    public function coi()
    {
        return $this->belongsTo(Coi::class);
    }
}
