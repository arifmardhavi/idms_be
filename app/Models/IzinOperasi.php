<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IzinOperasi extends BaseModel
{
    use HasFactory;
    protected $fillable = ['unit_id', 'no_certificate', 'issue_date', 'overdue_date', 'izin_operasi_certificate', 'izin_operasi_old_certificate', 'rla', 'rla_issue', 'rla_overdue', 'rla_certificate', 'rla_old_certificate'];
    protected $appends = ['due_days', 'rla_due_days'];

    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($izin_operasi) {
            // Hapus file sertifikat izin_operasi jika ada
            if ($izin_operasi->izin_operasi_certificate) {
                $filePath = public_path('izin_operasi/certificates/' . $izin_operasi->izin_operasi_certificate);
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
            }

            // Hapus file sertifikat izin_operasi lama jika ada
            if ($izin_operasi->izin_operasi_old_certificate) {
                $filePath = public_path('izin_operasi/certificates/' . $izin_operasi->izin_operasi_old_certificate);
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
            }

            // Hapus file sertifikat RLA jika ada
            if ($izin_operasi->rla_certificate) {
                $filePath = public_path('izin_operasi/rla/' . $izin_operasi->rla_certificate);
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
            }
            
            // Hapus file sertifikat RLA lama jika ada
            if ($izin_operasi->rla_old_certificate) {
                $filePath = public_path('izin_operasi/rla/' . $izin_operasi->rla_old_certificate);
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
            }
            // Hapus semua laporan izin_operasi yang terkait
            foreach ($izin_operasi->reportIzinOperasi as $report) {
                // Hapus file laporan izin_operasi jika ada
                if ($report->report_izin_operasi) {
                    $filePath = public_path('izin_operasi/reports/' . $report->report_izin_operasi);
                    if (file_exists($filePath)) {
                        unlink($filePath);
                    }
                }
                // Hapus record laporan izin_operasi
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
    public function reportIzinOperasi()
    {
        return $this->hasMany(ReportIzinOperasi::class);
    }
}