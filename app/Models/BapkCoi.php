<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BapkCoi extends BaseModel
{
    use HasFactory;
    protected $fillable = ["coi_id", 'bapk_coi'];
    
    public function coi()
    {
        return $this->belongsTo(Coi::class);
    }
}
