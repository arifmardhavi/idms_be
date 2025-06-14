<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Datasheet extends Model
{
    use HasFactory;
    protected $table = 'datasheets';
    protected $fillable = [
        'engineering_data_id',
        'datasheet_file',
    ];
    public function engineeringData()
    {
        return $this->belongsTo(EngineeringData::class);
    }
}
