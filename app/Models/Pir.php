<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pir extends BaseModel
{
    use HasFactory;
    protected $fillable = [
        'judul',
        'tanggal_pir',
        'historical_memorandum_id',
        'pir_file',
    ];
    
    protected $appends = ['memorandum_file'];
    protected $hidden = ['historical_memorandum'];
    
    public function historical_memorandum()
    {
        return $this->belongsTo(HistoricalMemorandum::class);
    }
    public function getMemorandumFileAttribute()
    {
        return $this->historical_memorandum ? $this->historical_memorandum->memorandum_file : null;
    }

}
