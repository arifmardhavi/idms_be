<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EngineeringData extends Model
{
    use HasFactory;
    protected $table = 'engineering_data';
    protected $fillable = [
        'tag_number_id',
    ];
    protected $appends = [
        'ga_drawings_count',
        'datasheets_count',
    ];
    public function getGaDrawingsCountAttribute()
    {
        return $this->gaDrawings()->count();
    }
    public function getDatasheetsCountAttribute()
    {
        return $this->datasheets()->count();
    }

    public function tagNumber()
    {
        return $this->belongsTo(Tag_number::class);
    }
    public function gaDrawings()
    {
        return $this->hasMany(GaDrawing::class, 'engineering_data_id');
    }
    public function datasheets()
    {
        return $this->hasMany(Datasheet::class, 'engineering_data_id');
    }
    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($engineeringData) {
            // Hapus semua GA Drawing yang terkait
            foreach ($engineeringData->gaDrawings as $gaDrawing) {
                // Hapus file dari storage
                if ($gaDrawing->drawing_file) { // hanya proses jika tidak null
                    $filePath = public_path('engineering_data/ga_drawing/' . $gaDrawing->drawing_file);
                    if (file_exists($filePath)) {
                        unlink($filePath);
                    }
                }
                // Hapus record-nya
                $gaDrawing->delete();
            }
            // Hapus semua Datasheet yang terkait
            foreach ($engineeringData->datasheets as $datasheet) {
                // Hapus file dari storage
                if ($datasheet->datasheet_file) { // hanya proses jika tidak null
                    $filePath = public_path('engineering_data/datasheet/' . $datasheet->datasheet_file);
                    if (file_exists($filePath)) {
                        unlink($filePath);
                    }
                }
                // Hapus record-nya
                $datasheet->delete();
            }
        });
    }
}
