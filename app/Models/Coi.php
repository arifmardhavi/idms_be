<?php

namespace App\Models;

use App\Support\PsvTagNumber;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Coi extends BaseModel
{
    use HasFactory;
    protected $fillable = ["plo_id", 'tag_number_id', 'no_certificate', 'issue_date', 'overdue_date', 'coi_certificate',"coi_old_certificate" , 'rla', 'rla_issue', 'rla_overdue', 'rla_certificate', 'rla_old_certificate', 're_engineer', 're_engineer_certificate'];
    protected $appends = [
        'due_days', 
        'rla_due_days',
        'count_report_coi',
        'count_bapk_coi',
    ];

    /**
     * Setiap COI baru yang tag number-nya PSV/TSV langsung mendapat baris
     * Monitoring PSV (semua kolom manual masih kosong) supaya bisa diisi user
     * tanpa perlu menyewa sync manual. Idempotent — aman kalau listener
     * terpanggil ulang.
     */
    protected static function booted(): void
    {
        static::created(function (self $coi) {
            if (! PsvTagNumber::matches($coi->tag_number?->tag_number)) {
                return;
            }

            app(\App\Services\MonitoringPsvSyncService::class)->syncFromCoi($coi);
        });
    }

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

    public function reportCoi()
    {
        return $this->hasMany(ReportCoi::class);
    }

    public function bapkCoi()
    {
        return $this->hasMany(BapkCoi::class);
    }

    public function getCountReportCoiAttribute()
    {
        return $this->reportCoi()->count();
    }

    public function getCountBapkCoiAttribute()
    {
        return $this->bapkCoi()->count();
    }
}
