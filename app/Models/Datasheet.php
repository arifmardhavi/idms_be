<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Datasheet extends BaseModel
{
    use HasFactory;
    protected $table = 'datasheets';
    protected $fillable = [
        'no_dokumen',
        'engineering_data_id',
        'datasheet_file',
        'date_datasheet',
    ];
    public function engineeringData()
    {
        return $this->belongsTo(EngineeringData::class);
    }
}
