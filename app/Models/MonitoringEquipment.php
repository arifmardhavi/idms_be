<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MonitoringEquipment extends BaseModel
{
    use HasFactory;
    protected $fillable = [
        'tag_number_id',
        'criticality',
        'sece',
        'status',
        'tindak_lanjut',
        'target',
    ];


    public function tagNumber()
    {
        return $this->belongsTo(Tag_number::class);
    }

}
