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

    protected $casts = [
        'contract_date' => 'date',
        'contract_start_date' => 'date',
        'contract_end_date' => 'date',
        'contract_price' => 'integer',
    ];

    protected $appends = [
        'durasi_mpp',
        'kom',
        'sisa_nilai',
        'plan_progress',
        'actual_progress',
        'deviation_progress',
        'monitoring_progress',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELATIONSHIPS
    |--------------------------------------------------------------------------
    */

    public function users()
    {
        return $this->belongsToMany(User::class);
    }

    public function termin()
    {
        return $this->hasMany(TerminNew::class, 'contract_new_id');
    }

    public function lumpsum_progress()
    {
        return $this->hasMany(LumpsumProgressNew::class, 'contract_new_id');
    }

    public function spk()
    {
        return $this->hasMany(SpkNew::class, 'contract_new_id');
    }

    public function amandemen()
    {
        return $this->hasMany(AmandemenNew::class, 'contract_new_id');
    }

    public function terminReceipt()
    {
        return $this->hasManyThrough(
            TerminReceiptNew::class,
            TerminNew::class,
            'contract_new_id',
            'termin_new_id',
            'id',
            'id'
        );
    }

    public function allSpkProgress()
    {
        return $this->hasManyThrough(
            SpkProgressNew::class,
            SpkNew::class,
            'contract_new_id',
            'spk_new_id',
            'id',
            'id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | MODEL EVENT
    |--------------------------------------------------------------------------
    */

    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($contract) {

            if ($contract->contract_file) {
                $filePath = public_path('contract_new/' . $contract->contract_file);
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
            }

            if ($contract->meeting_notes) {
                $filePath = public_path('contract_new/meeting_notes/' . $contract->meeting_notes);
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
            }
        });
    }

    /*
    |--------------------------------------------------------------------------
    | ACCESSOR
    |--------------------------------------------------------------------------
    */

    public function getDurasiMppAttribute()
    {
        if (!$this->contract_end_date) {
            return [
                'sisa' => null,
                'color' => 'green'
            ];
        }

        $now = now();
        $endDate = Carbon::parse($this->contract_end_date);
        $sisaHari = $now->diffInDays($endDate, false);

        $adaAmandemen = $this->relationLoaded('amandemen')
            ? $this->amandemen->isNotEmpty()
            : $this->amandemen()->exists();

        $adaPenagihan = $this->termin()
            ->whereHas('receipts')
            ->exists();

        if ($this->contract_status == 0) {
            $color = 'blue';
        } elseif ($sisaHari <= 0 && !$adaAmandemen) {
            $color = 'red';
        } elseif ($sisaHari <= 28 && !$adaPenagihan) {
            $color = 'yellow';
        } else {
            $color = 'green';
        }

        return [
            'sisa' => $sisaHari,
            'color' => $color
        ];
    }

    public function hasAmandemenUnuploaded()
    {
        return $this->amandemen->contains(function ($item) {
            return !$item->ba_agreement_file || !$item->result_amandemen_file;
        });
    }

    public function getMonitoringProgressAttribute()
    {
        if ($this->contract_type == 2) {
            $plan = $this->latestPlanProgressSpk() ?? 0;    // asumsikan return persentase 0-100
            $actual = $this->latestActualProgressSpk() ?? 0; // asumsikan return persentase 0-100
        } else {
            $plan = $this->lumpsum_progress()->latest()->value('plan') ?? 0;    // asumsikan return persentase 0-100
            $actual = $this->lumpsum_progress()->latest()->value('actual') ?? 0; // asumsikan return persentase 0-100
        }

        $status = $this->contract_status;

        if ($status == 0) { // selesai
            return [
                'deviation' => abs($actual - $plan),
                'color' => 'blue',
            ];
        }

        $deviation = round($actual - $plan, 2); // hasil: 0.01 (float)
        if ($this->hasAmandemenUnuploaded()) {
            return [
                'deviation' => $deviation,
                'color' => 'black',
            ];
        }else{
            if ($deviation >= 0) {
                $color = 'green';
            } elseif ($deviation > -20) {
                $color = 'yellow';
            } else {
                $color = 'red';
            }
        }



        return [
            'deviation' => $deviation,
            'color' => $color
        ];
    }

    public function getKomAttribute()
    {
        if (in_array($this->contract_type, [3,4])) {
            return 0;
        }

        if ($this->contract_start_date && $this->contract_end_date) {
            return 1;
        }

        return 0;
    }

    public function getSisaNilaiAttribute()
    {
        $nilaiKontrak = $this->contract_price ?? 0;

        if ($this->contract_type == 2) {

            $totalPenagihan = $this->relationLoaded('spk')
                ? $this->spk->sum('receipt_nominal')
                : $this->spk()->sum('receipt_nominal');

        } else {

            $totalPenagihan = $this->relationLoaded('terminReceipt')
                ? $this->terminReceipt->sum('receipt_nominal')
                : $this->terminReceipt()->sum('termin_receipt_news.receipt_nominal');

        }

        $denda = $this->contract_penalty ?? 0;

        $sisaNilai = $nilaiKontrak - $totalPenagihan - $denda;

        if ($this->contract_status == 0) {
            $color = 'blue';
        } elseif ($sisaNilai <= 0) {
            $color = 'red';
        } elseif ($sisaNilai <= ($nilaiKontrak * 0.2)) {
            $color = 'yellow';
        } else {
            $color = 'green';
        }

        return [
            'sisa' => $sisaNilai,
            'nilai' => $nilaiKontrak,
            'denda' => $denda,
            'totalPenagihan' => $totalPenagihan,
            'color' => $color
        ];
    }

    public function getActualProgressAttribute()
    {
        if ($this->contract_type == 2) {
            return $this->allSpkProgress()->latest()->value('actual') ?? 0;
        }else{
            return $this->lumpsum_progress()->latest()->value('actual') ?? 0;
        }

        return $this->lumpsum_progress()
            ->latest()
            ->value('actual') ?? 0;
    }

    public function getPlanProgressAttribute()
    {
        if ($this->contract_type == 2) {
            return $this->allSpkProgress()->latest()->value('plan') ?? 0;
        }

        if ($this->relationLoaded('lumpsum_progress')) {
            return $this->lumpsum_progress
                ->sortByDesc('created_at')
                ->first()->plan ?? 0;
        }

        return $this->lumpsum_progress()
            ->latest()
            ->value('plan') ?? 0;
    }

    public function getDeviationProgressAttribute()
    {
        return $this->actual_progress - $this->plan_progress;
    }
}