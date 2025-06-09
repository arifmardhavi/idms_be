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
        'unit_id',
        'category_id',
        'tag_number_id',
        'no_dokumen',
        'perihal',
        'tipe_memorandum',
        'tanggal_terbit',
        'memorandum_file',
    ];

    public function tag_number()
    {
        return $this->belongsTo(Tag_number::class);
    }
    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

}
