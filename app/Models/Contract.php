<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contract extends Model
{
    use HasFactory;
    protected $fillable = ['no_vendor', 'vendor_name', 'no_contract', 'contract_name', 'contract_type', 'contract_date', 'contract_price', 'contract_file', 'kom', 'contract_start_date', 'contract_end_date', 'meeting_notes', 'contract_status', 'initial_contract_price', 'contract_penalty', 'pengawas'];
    protected $appends = [
        'weeks',
        'durasi_mpp',
        'monitoring_progress',
        'sisa_nilai',
    ];

    public function termin()
    {
        return $this->hasMany(Termin::class);
    }

    public function lumpsum_progress()
    {
        return $this->hasMany(Lumpsum_progress::class);
    }

    public function spk()
    {
        return $this->hasMany(Spk::class);
    }

    public function amandemen()
    {
        return $this->hasMany(Amandemen::class);
        
    }

    public function allSpkProgress()
    {
        return $this->hasManyThrough(
            \App\Models\Spk_progress::class,
            \App\Models\Spk::class,
            'contract_id', // foreign key di Spk
            'spk_id',      // foreign key di SpkProgress
            'id',          // local key di Contract
            'id'           // local key di Spk
        );
    }

    public function termBillings()
    {
        return $this->hasManyThrough(
            \App\Models\TermBilling::class,  // Model yang dituju
            \App\Models\Termin::class,       // Model perantara
            'contract_id',                   // Foreign key di termin
            'termin_id',                     // Foreign key di term_billing
            'id',                            // Local key di contract
            'id'                             // Local key di termin
        );
    }


    public function latestPlanProgressSpk()
    {
        return $this->allSpkProgress()
            ->orderByDesc('week')
            ->value('plan_progress');
    }

    public function latestActualProgressSpk()
    {
        return $this->allSpkProgress()
            ->orderByDesc('week')
            ->value('actual_progress');
    }

    public function hasAmandemenUnuploaded()
    {
        return $this->amandemen->contains(function ($item) {
            return !$item->ba_agreement_file || !$item->result_amandemen_file;
        });
    }


    public function getWeeksAttribute()
    {
        $start = Carbon::parse($this->contract_start_date);
        $end = Carbon::parse($this->contract_end_date);

        if (!$start->isFriday()) {
            $start = $start->next(Carbon::FRIDAY);
        }

        $weeks = [];
        $weekNumber = 1;

        while ($start->lte($end)) {
            $weekStart = $start->copy();
            $weekEnd = $weekStart->copy()->addDays(6);
            if ($weekEnd->gt($end)) {
                $weekEnd = $end->copy();
            }

            $weeks[] = [
                'week' => $weekNumber,
                'start' => $weekStart->format('Y-m-d'),
                'end' => $weekEnd->format('Y-m-d'),
                'label' => "Week {$weekNumber} ({$weekStart->format('d M')} - {$weekEnd->format('d M Y')})",
                'value' => "{$weekStart->format('Y-m-d')}_{$weekEnd->format('Y-m-d')}",
            ];

            $weekNumber++;
            $start = $weekStart->addDays(7);
        }

        return $weeks;
    }

    public function getDurasiMppAttribute()
    {
        $now = now();
        $endDate = \Carbon\Carbon::parse($this->contract_end_date);
        $sisaHari = $now->diffInDays($endDate, false);

        $adaAmandemen = $this->amandemen()->exists();
        $adaPenagihan = $this->termin()->whereHas('termBilling')->exists();
        $status = $this->contract_status;

        // Tentukan warna sesuai aturan
        if ($status == 0) { // selesai
            $color = 'blue';
        }elseif ($sisaHari <= 0 && !$adaAmandemen) {
            $color = 'red';
        } elseif ($sisaHari <= 28 && !$adaPenagihan) {
            $color = 'yellow';
        } else {
            $color = 'green';
        }

        return [
            'sisa' => max(0, $sisaHari),
            'color' => $color
        ];
    }

    public function getMonitoringProgressAttribute()
    {
        if ($this->contract_type == 2) {
            $plan = $this->latestPlanProgressSpk() ?? 0;    // asumsikan return persentase 0-100
            $actual = $this->latestActualProgressSpk() ?? 0; // asumsikan return persentase 0-100
        } else {
            $plan = $this->lumpsum_progress()->latest()->value('plan_progress') ?? 0;    // asumsikan return persentase 0-100
            $actual = $this->lumpsum_progress()->latest()->value('actual_progress') ?? 0; // asumsikan return persentase 0-100
        }

        $status = $this->contract_status;

        if ($status == 0) { // selesai
            return [
                'deviation' => abs($actual - $plan),
                'color' => 'blue',
            ];
        }

        $deviation = abs($actual - $plan);
        if ($this->hasAmandemenUnuploaded()) {
            return [
                'deviation' => $deviation,
                'color' => 'black',
            ];
        }


        if ($deviation == 0) {
            $color = 'green';
        } elseif ($deviation <= 20) {
            $color = 'yellow';
        } else {
            $color = 'red';
        }

        return [
            'deviation' => $deviation,
            'color' => $color
        ];
    }

    public function getSisaNilaiAttribute()
    {
        $nilaiKontrak = $this->contract_price;
        if ($this->contract_type == 2) {
            $totalPenagihan = $this->spk()->sum('invoice_value');
        }else{
            $totalPenagihan = $this->termBillings()->sum('billing_value');
        }
        $denda = $this->contract_penalty ?? 0;

        $sisaNilai = $nilaiKontrak - $totalPenagihan - $denda;
        // $sisaNilai = max(0, $sisaNilai);

        // Warna bisa kamu buat berdasarkan sisa nilai (contoh):
        if ($this->contract_status == 0) { // selesai
            $color = 'blue';
        }elseif ($sisaNilai <= 0) {
            $color = 'red';
        } elseif ($sisaNilai <= ($nilaiKontrak * 0.2)) { // kurang dari 20% sisa
            $color = 'yellow';
        } else {
            $color = 'green';
        }

        return [
            'sisa' => $sisaNilai,
            'color' => $color
        ];
    }



}
