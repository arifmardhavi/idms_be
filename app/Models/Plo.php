<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Plo extends Model
{
    use HasFactory;
    protected $fillable = ['unit_id', 'no_certificate', 'issue_date', 'overdue_date', 'plo_certificate', 'plo_old_certificate', 'rla', 'rla_issue', 'rla_overdue', 'rla_certificate', 'rla_old_certificate'];
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
    
    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }
}
