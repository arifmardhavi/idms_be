<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Moc extends BaseModel
{
    use HasFactory;
    protected $table = 'mocs';
    protected $fillable = [
        'unit_id',
        'category_id',
        'tag_number_id',
        'no_dokumen',
        'perihal',
        'tipe_moc',
        'tanggal_terbit',
        'moc_file',
    ];

    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($moc) {
            // Hapus semua lampiran yang terkait
            foreach ($moc->lampiran_moc as $lampiran) {
                // Hapus file dari storage
                if ($lampiran->lampiran_moc) { // hanya proses jika tidak null
                    $filePath = public_path('moc/lampiran/' . $lampiran->lampiran_moc);
                    if (file_exists($filePath)) {
                        unlink($filePath);
                    }
                }

                // Hapus record-nya
                $lampiran->delete();
            }

            // JANGAN LUPA: Hapus file memorandum utamanya juga
            if ($moc->moc_file) { // hanya proses jika tidak null
                $mainFile = public_path('moc/' . $moc->moc_file);
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

}
