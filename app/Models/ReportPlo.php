<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReportPlo extends Model
{
    use HasFactory;
    protected $fillable = ["plo_id", 'report_plo'];
    
    public function plo()
    {
        return $this->belongsTo(Plo::class);
    }
}
