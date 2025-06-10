<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LampiranMemo extends Model
{
    use HasFactory;
    protected $fillable = ['historical_memorandum_id', 'lampiran_memo'];

    public function historicalMemorandum()
    {
        return $this->belongsTo(HistoricalMemorandum::class);
    }
}
