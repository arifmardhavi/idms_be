<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReadinessJasa extends Model
{
    use HasFactory;
    protected $fillable = ['event_readiness_id','jasa_name', 'status'];
    protected $appends = [
        'ta_status',
        'last_number_status',
        'prognosa',
        'total_progress',
    ];


    public function event_readiness()
    {
        return $this->belongsTo(EventReadiness::class, 'event_readiness_id');
    }

    public function rekomendasi_jasa()
    {
        return $this->hasOne(RekomendasiJasa::class);
    }
    public function notif_jasa()
    {
        return $this->hasOne(NotifJasa::class);
    }
    public function job_plan_jasa()
    {
        return $this->hasOne(JobPlanJasa::class);
    }
    public function pr_jasa()
    {
        return $this->hasOne(PrJasa::class);
    }
    public function tender_jasa()
    {
        return $this->hasOne(TenderJasa::class);
    }
    public function contract_jasa()
    {
        return $this->hasOne(ContractJasa::class);
    }


    /**
     * last_target_status:
     * - jika tidak ada step terisi => null
     * - days_remaining = diff ke target_date (step terakhir terisi)
     * - color = rule berdasarkan nilai paling mendesak antara diffTarget dan diffTa (min)
     * - tetap mengembalikan source = 'target_date' karena date yang dipakai adalah target_date
     */
    // public function getLastTargetStatusAttribute()
    // {
    //     // urutan dari belakang ke depan (prioritas step terakhir terisi)
    //     $steps = [
    //         $this->tender_jasa,
    //         $this->pr_jasa,
    //         $this->job_plan_jasa,
    //         $this->notif_jasa,
    //         $this->rekomendasi_jasa,
    //     ];

    //     $lastTargetDate = null;
    //     $stepUsed = null;
    //     foreach ($steps as $step) {
    //         if ($step && !empty($step->target_date)) {
    //             $lastTargetDate = Carbon::parse($step->target_date);
    //             $stepUsed = $step;
    //             break;
    //         }
    //     }

    //     // Jika tidak ada step terisi -> tetap null (tidak fallback ke tanggal_ta)
    //     if (!$lastTargetDate) {
    //         return null;
    //     }

    //     $now = Carbon::now();
    //     $diffTarget = $now->diffInDays($lastTargetDate, false); // bisa negatif

    //     // hitung diff tanggal_ta jika tersedia
    //     $diffTa = null;
    //     if (!empty($this->event_readiness->tanggal_ta)) {
    //         $taDate = Carbon::parse($this->event_readiness->tanggal_ta);
    //         $diffTa = $now->diffInDays($taDate, false);
    //     }

    //     // tentukan color berdasarkan nilai yang lebih mendesak (min)
    //     $colorDiff = $diffTarget;
    //     if ($diffTa !== null && $diffTa < $colorDiff) {
    //         $colorDiff = $diffTa;
    //     }
    //     $color = $this->getColorByDiff($colorDiff);

    //     return [
    //         // nilai hari yang ditampilkan tetap dari target_date
    //         'days_remaining' => $diffTarget,
    //         'color' => $color,
    //         'date_used' => $lastTargetDate->toDateString(),
    //         'step_used' => $stepUsed ? class_basename($stepUsed) : null,
    //         // opsional: sertakan kedua diff agar frontend bisa menampilkan detail
    //         'diff_target' => $diffTarget,
    //         'diff_ta' => $diffTa,
    //     ];
    // }

    /**
     * ta_status: hanya berdasar tanggal_ta (terpisah)
     */
    public function getTaStatusAttribute()
    {
        if (empty($this->event_readiness->tanggal_ta)) {
            return null;
        }

        $taDate = Carbon::parse($this->event_readiness->tanggal_ta);
        $diff = Carbon::now()->diffInDays($taDate, false);

        return [
            'days_remaining' => $diff,
            'color' => $this->getColorByDiff($diff),
        ];
    }

    /**
     * helper color rule
     */
    private function getColorByDiff($diff)
    {
        // jika diff null, kembalikan null (tidak ada data)
        if ($diff === null) {
            return null;
        }

        // diff negatif => sudah terlewat => merah
        if ($diff < 0) {
            return 'red';
        }

        if ($diff > 15) {
            return 'green';
        } elseif ($diff >= 5) {
            return 'yellow';
        }

        return 'red';
    }


    public function getLastNumberStatusAttribute()
    {
        // urutan prioritas dari belakang ke depan
        $steps = [
            'po_jasa'      => 'no_po',
            'pr_jasa'      => 'no_pr',
            'job_plan_jasa'=> 'no_wo',
            'notif_jasa'   => 'no_notif',
        ];

        foreach ($steps as $relation => $field) {
            if ($this->$relation && !empty($this->$relation->$field)) {
                // contoh output: "PO (12345)"
                $stepName = strtoupper(str_replace('_jasa', '', $relation)); 
                return $stepName . " " . $this->$relation->$field;
            }
        }

        return null; // kalau semua kosong
    }

    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($readiness) {
            // Mapping: relasi => [field => folder]
            $relations = [
                'rekomendasi_jasa' => ['rekomendasi_file' => 'readiness_ta/jasa/rekomendasi'],
                'po_jasa'          => ['po_file' => 'readiness_ta/jasa/po'],
                'job_plan_jasa'    => [
                    'boq_file'  => 'readiness_ta/jasa/job_plan/boq',
                    'kak_file'  => 'readiness_ta/jasa/job_plan/kak',
                ],
            ];

            foreach ($relations as $relation => $fields) {
                $model = $readiness->$relation;
                // dd($fields);
                if ($model) {
                    foreach ($fields as $field => $folder) {
                        if (!empty($model->$field)) {
                            $path = public_path($folder . '/' . $model->$field);
                            if (file_exists($path)) {
                                unlink($path); // pakai @ biar silent kalau gagal
                            }
                        }
                    }
                    $model->delete(); // hapus record di DB juga
                }
            }
        });
    }

    public function getPrognosaAttribute()
    {
        if (empty($this->event_readiness->tanggal_ta)) {
            return null;
        }

        if (!$this->job_plan_jasa || empty($this->job_plan_jasa->durasi_preparation)) {
            return null;
        }

        $taDate = Carbon::parse($this->event_readiness->tanggal_ta);
        $today = Carbon::now();

        // selisih langsung ke TA
        $diffTa = $today->diffInDays($taDate, false);

        // days_remaining = sisa hari - durasi preparation
        $prepDays = (int) $this->job_plan_jasa->durasi_preparation;
        $daysRemaining = $diffTa - $prepDays;

        // tentukan warna
        if ($daysRemaining > 60) {
            $color = 'green'; 
        } elseif ($daysRemaining >= 30 && $daysRemaining <= 60) {
            $color = 'yellow';
        } else {
            $color = 'red'; // 0 - 29 hari atau sudah lewat
        }

        return [
            'days_remaining' => $daysRemaining,
            'durasi_preparation' => $prepDays,
            'tanggal_ta' => $this->event_readiness->tanggal_ta,
            'color' => $color,
        ];
    }

    public function getTotalProgressAttribute()
    {
        // urutan step dan modelnya
        $steps = [
            'rekomendasi_jasa',
            'notif_jasa',
            'job_plan_jasa',
            'pr_jasa',
            'tender_jasa',
            'contract_jasa',
        ];

        $lastStep = null;
        $lastStatus = null;

        $this->loadMissing([
            'rekomendasi_jasa',
            'notif_jasa',
            'job_plan_jasa',
            'pr_jasa',
            'tender_jasa',
            'contract_jasa',
        ]);


        // cari step terakhir yang ada datanya
        foreach ($steps as $step) {
            if ($this->$step) {
                $lastStep = $step;
                $lastStatus = $this->$step->status ?? null;
            }
        }

        // kalau tidak ada step sama sekali
        if (!$lastStep) {
            return "0%";
        }

        // dapatkan posisi step terakhir
        $stepIndex = array_search($lastStep, $steps) + 1; // +1 karena index mulai dari 0

        // hitung nilai status (0 = 1, 1 = 0.5, selainnya = 0)
        $statusValue = match($lastStatus) {
            0 => 1,
            1 => 0.5,
            default => 0,
        };

        // hitung progress
        $progress = (($stepIndex - 1) + $statusValue) / count($steps) * 100;

        // format dengan 2 angka di belakang koma
        return number_format($progress, 2) . '%';
    }

    
}
