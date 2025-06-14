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

    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($historicalMemorandum) {
            // Hapus semua lampiran yang terkait
            foreach ($historicalMemorandum->lampiran_memo as $lampiran) {
                // Hapus file dari storage
                if ($lampiran->lampiran_memo) { // hanya proses jika tidak null
                    $filePath = public_path('historical_memorandum/lampiran/' . $lampiran->lampiran_memo);
                    if (file_exists($filePath)) {
                        unlink($filePath);
                    }
                }

                // Hapus record-nya
                $lampiran->delete();
            }

            // JANGAN LUPA: Hapus file memorandum utamanya juga
            if ($historicalMemorandum->memorandum_file) { // hanya proses jika tidak null
                $mainFile = public_path('historical_memorandum/' . $historicalMemorandum->memorandum_file);
                if (file_exists($mainFile)) {
                    unlink($mainFile);
                }
            }
        });
    }

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

    public function lampiran_memo()
    {
        return $this->hasMany(LampiranMemo::class, 'historical_memorandum_id');
    }

    


}
