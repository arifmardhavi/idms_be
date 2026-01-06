<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IzinDisnaker extends BaseModel
{
    use HasFactory;
    protected $fillable = ['unit_id', 'no_certificate', 'issue_date', 'overdue_date', 'izin_disnaker_certificate', 'izin_disnaker_old_certificate', 'rla', 'rla_issue', 'rla_overdue', 'rla_certificate', 'rla_old_certificate'];
    protected $appends = ['due_days', 'rla_due_days'];

    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($izin_disnaker) {
            // Hapus file sertifikat izin_disnaker jika ada
            if ($izin_disnaker->izin_disnaker_certificate) {
                $filePath = public_path('izin_disnaker/certificates/' . $izin_disnaker->izin_disnaker_certificate);
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
            }

            // Hapus file sertifikat izin_disnaker lama jika ada
            if ($izin_disnaker->izin_disnaker_old_certificate) {
                $filePath = public_path('izin_disnaker/certificates/' . $izin_disnaker->izin_disnaker_old_certificate);
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
            }

            // Hapus file sertifikat RLA jika ada
            if ($izin_disnaker->rla_certificate) {
                $filePath = public_path('izin_disnaker/rla/' . $izin_disnaker->rla_certificate);
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
            }
            
            // Hapus file sertifikat RLA lama jika ada
            if ($izin_disnaker->rla_old_certificate) {
                $filePath = public_path('izin_disnaker/rla/' . $izin_disnaker->rla_old_certificate);
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
            }
            // Hapus semua laporan izin_disnaker yang terkait
            foreach ($izin_disnaker->reportIzinDisnaker as $report) {
                // Hapus file laporan izin_disnaker jika ada
                if ($report->report_izin_disnaker) {
                    $filePath = public_path('izin_disnaker/reports/' . $report->report_izin_disnaker);
                    if (file_exists($filePath)) {
                        unlink($filePath);
                    }
                }
                // Hapus record laporan izin_disnaker
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
    public function reportIzinDisnaker()
    {
        return $this->hasMany(ReportIzinDisnaker::class);
    }
}
