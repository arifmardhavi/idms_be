<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContractNew extends Model
{
    use HasFactory;
    protected $fillable = [
        'no_vendor',
        'vendor_name',
        'no_contract',
        'contract_name',
        'contract_type',
        'contract_date',
        'contract_price',
        'contract_file',
        'current_status',
        'contract_start_date',
        'contract_end_date',
        'meeting_notes',
        'pengawas',
        'contract_status',
    ];
    protected $appends = [
        'durasi_mpp',
        // 'total_weeks',
        // 'weeks',
        // 'sisa_nilai',
        // 'actual_progress',
        // 'plan_progress',
        // 'deviation_progress',
    ];

    public function users()
    {
        return $this->belongsToMany(User::class);
    }

    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($contract) {
            // Hapus file kontrak jika ada
            if ($contract->contract_file) {
                $filePath = public_path('contract_new/' . $contract->contract_file);
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
            }
            // Hapus meeting notes yang terkait
            if ($contract->meeting_notes) {
                $filePath = public_path('contract_new/meeting_notes/' . $contract->meeting_notes);
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
            }

        });
    }

    public function getDurasiMppAttribute()
    {
        $now = now()->startOfDay();
        if ($this->contract_start_date && $this->contract_end_date) {
            $end = Carbon::parse($this->contract_end_date)->startOfDay();
            return $now->diffInDays($end, false);
        }
        return null;
    }
}
