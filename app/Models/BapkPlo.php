<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BapkPlo extends BaseModel
{
    use HasFactory;
    protected $fillable = ["plo_id", 'bapk_plo'];
    
    public function plo()
    {
        return $this->belongsTo(Plo::class);
    }
}
