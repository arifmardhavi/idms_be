<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Plo extends BaseModel
{
    use HasFactory;
    protected $fillable = ['unit_id', 'no_certificate', 'issue_date', 'overdue_date', 'plo_certificate', 'plo_old_certificate', 'rla', 'rla_issue', 'rla_overdue', 'rla_certificate', 'rla_old_certificate'];
    protected $appends = ['due_days', 'rla_due_days'];

    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($plo) {
            // Hapus file sertifikat PLO jika ada
            if ($plo->plo_certificate) {
                $filePath = public_path('plo/certificates/' . $plo->plo_certificate);
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
            }

            // Hapus file sertifikat PLO lama jika ada
            if ($plo->plo_old_certificate) {
                $filePath = public_path('plo/certificates/' . $plo->plo_old_certificate);
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
            }

            // Hapus file sertifikat RLA jika ada
            if ($plo->rla_certificate) {
                $filePath = public_path('plo/rla/' . $plo->rla_certificate);
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
            }
            
            // Hapus file sertifikat RLA lama jika ada
            if ($plo->rla_old_certificate) {
                $filePath = public_path('plo/rla/' . $plo->rla_old_certificate);
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
            }
            // Hapus semua laporan PLO yang terkait
            foreach ($plo->reportPlo as $report) {
                // Hapus file laporan PLO jika ada
                if ($report->report_plo) {
                    $filePath = public_path('plo/reports/' . $report->report_plo);
                    if (file_exists($filePath)) {
                        unlink($filePath);
                    }
                }
                // Hapus record laporan PLO
                $report->delete();
            }
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
    
    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }
    public function reportPlo()
    {
        return $this->hasMany(ReportPlo::class);
    }
}
