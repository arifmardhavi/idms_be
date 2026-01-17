<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SertifikatKalibrasi extends Model
{
    use HasFactory;
    protected $fillable = [
        'tag_number_id',
        'no_sertifikat_kalibrasi',
        'issue_date',
        'overdue_date',
        'file_sertifikat_kalibrasi',
        'file_old_sertifikat_kalibrasi',
    ];
    protected $appends = ['due_days', 'type_id', 'category_id', 'unit_id'];

    public function getDueDaysAttribute()
    {
        return $this->calculateDaysDifference($this->overdue_date);
    }

    private function calculateDaysDifference($date)
    {
        if (!$date) {
            return null;
        }

        $targetTimestamp = strtotime($date);
        $todayTimestamp = strtotime(now()->toDateString());

        return ($targetTimestamp - $todayTimestamp) / 86400; // 86400 = jumlah detik dalam sehari
    }
    
    public function tag_number()
    {
        return $this->belongsTo(Tag_number::class);
    }
    public function getTypeIdAttribute()
    {
        return $this->tag_number ? $this->tag_number->type_id : null;
    }
    public function getCategoryIdAttribute()
    {
        return $this->tag_number ? $this->tag_number->type->category_id : null;
    }
    public function getUnitIdAttribute()
    {
        return $this->tag_number ? $this->tag_number->unit_id : null;
    }
}
