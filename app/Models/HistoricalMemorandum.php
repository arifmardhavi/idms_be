<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HistoricalMemorandum extends Model
{
    use HasFactory;
    protected $table = 'historical_memorandum';
    protected $primaryKey = 'id';
    protected $fillable = [
        'tag_number_id',
        'judul_memorandum',
        'jenis_memorandum',
        'jenis_pekerjaan',
        'memorandum_file',
    ];

    public function tag_number()
    {
        return $this->belongsTo(Tag_number::class);
    }

}
