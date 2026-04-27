<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReadinessJasaRtnrt extends BaseModel
{
    use HasFactory;
    protected $fillable = [
        'event_readiness_rtnrt_id',
        'jasa_name',
        'price_estimate',
        'tanggal_target',
        'current_status',
        'status',
    ];

    protected $appends = [
        'rtnrt_status',
        'last_number_status',
        'prognosa',
        'total_progress',
    ];

    public function event_readiness_rtnrt()
    {
        return $this->belongsTo(EventReadinessRtnrt::class);
    }


    public function rekomendasi_jasa_rtnrt()
    {
        return $this->hasOne(RekomendasiJasaRtnrt::class);
    }

    public function notif_jasa_rtnrt()
    {
        return $this->hasOne(NotifJasaRtnrt::class);
    }

    public function job_plan_jasa_rtnrt()
    {
        return $this->hasOne(JobPlanJasaRtnrt::class);
    }

    public function pr_jasa_rtnrt()
    {
        return $this->hasOne(PrJasaRtnrt::class);
    }

    public function tender_jasa_rtnrt()
    {
        return $this->hasOne(TenderJasaRtnrt::class);
    }

    public function contract_jasa_rtnrt()
    {
        return $this->hasOne(ContractJasaRtnrt::class);
    }

    /**
     * rtnrt_status: hanya berdasar tanggal_target (terpisah)
     */
    public function getRtnrtStatusAttribute()
    {
        if (empty($this->tanggal_target)) {
            return null;
        }

        if ($this->status === 0) { // sudah selesai
            return [
                'days_remaining' => 0,
                'color' => 'blue',
            ];
        }
        $today = Carbon::today();
        $targetDate = Carbon::parse($this->tanggal_target);
        $diff = $today->diffInDays($targetDate, false);
        return [
            'days_remaining' => $diff,
            'color' => $this->getColorByDiff($diff),
        ];
    }


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
        if ($this->contract_jasa) {
            return "PO " . $this->contract_jasa_rtnrt->contract_new->no_contract;
        }
        // urutan prioritas dari belakang ke depan
        $steps = [
            'pr_jasa_rtnrt'       => 'no_pr',
            'job_plan_jasa_rtnrt' => 'no_wo',
            'notif_jasa_rtnrt'    => 'no_notif',
        ];

        foreach ($steps as $relation => $field) {
            if ($this->$relation && !empty($this->$relation->$field)) {
                // output: "PO (12345)"
                $stepName = strtoupper(str_replace('_jasa_rtnrt', '', $relation));
                return $stepName . " " . $this->$relation->$field;
            }
        }

        return null; // kalau semua kosong
    }

    public function getPrognosaAttribute()
    {
        if (empty($this->tanggal_target)) {
            return null;
        }

        if (!$this->job_plan_jasa_rtnrt || empty($this->job_plan_jasa_rtnrt->durasi_preparation)) {
            return null;
        }

        $targetDate = Carbon::parse($this->tanggal_target);
        $today = Carbon::now();

        $prepDays = (int) $this->job_plan_jasa_rtnrt->durasi_preparation;

        // tanggal mulai preparation (prognosa)
        $prognosaDate = $targetDate->copy()->subDays($prepDays);

        // selisih hari dari hari ini ke prognosa
        $daysRemaining = $today->diffInDays($prognosaDate, false);

        if ($this->status == 0) {
            return [
                'date' => null,
                'durasi_preparation' => $prepDays,
                'tanggal_rtnrt' => $this->tanggal_target,
                'color' => 'blue',
            ];
        }

        // tentukan warna berdasarkan sisa hari ke prognosa
        if ($daysRemaining > 60) {
            $color = 'green';
        } elseif ($daysRemaining >= 30 && $daysRemaining <= 60) {
            $color = 'yellow';
        } else {
            $color = 'red';
        }

        return [
            'date' => $prognosaDate->toDateString(),
            // 'prognosa_date' => $prognosaDate->toDateString(),
            'durasi_preparation' => $prepDays,
            'tanggal_rtnrt' => $this->tanggal_target,
            'color' => $color,
        ];
    }

    public function getTotalProgressAttribute()
    {
        // urutan step dan modelnya
        $steps = [
            'rekomendasi_jasa_rtnrt',
            'notif_jasa_rtnrt',
            'job_plan_jasa_rtnrt',
            'pr_jasa_rtnrt',
            'tender_jasa_rtnrt',
            'contract_jasa_rtnrt',
        ];

        $lastStep = null;
        $lastStatus = null;

        $this->loadMissing([
            'rekomendasi_jasa_rtnrt',
            'notif_jasa_rtnrt',
            'job_plan_jasa_rtnrt',
            'pr_jasa_rtnrt',
            'tender_jasa_rtnrt',
            'contract_jasa_rtnrt',
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

    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($readiness) {
            // Mapping: relasi => [field => folder]
            $relations = [
                'rekomendasi_jasa_rtnrt' => ['rekomendasi_file' => 'readiness_rtnrt/jasa/rekomendasi'],
                'po_jasa_rtnrt'          => ['po_file' => 'readiness_rtnrt/jasa/po'],
                'job_plan_jasa_rtnrt'    => [
                    'boq_file'  => 'readiness_rtnrt/jasa/job_plan/boq',
                    'kak_file'  => 'readiness_rtnrt/jasa/job_plan/kak',
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
}
