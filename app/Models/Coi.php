<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Coi extends BaseModel
{
    use HasFactory;
    protected $fillable = ["plo_id", 'tag_number_id', 'no_certificate', 'issue_date', 'overdue_date', 'coi_certificate',"coi_old_certificate" , 'rla', 'rla_issue', 'rla_overdue', 'rla_certificate', 'rla_old_certificate', 're_engineer', 're_engineer_certificate'];
    protected $appends = ['due_days', 'rla_due_days'];

    public function getDueDaysAttribute()
    {
        return $this->calculateDaysDifference($this->overdue_date);
    }

    public function getRlaDueDaysAttribute()
    {
        return $this->calculateDaysDifference($this->rla_overdue);
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

    public function plo()
    {
        return $this->belongsTo(Plo::class);
    }
}
