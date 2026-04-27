<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReadinessMaterialRtnrt extends BaseModel
{
    use HasFactory;
    protected $fillable = [
        'event_readiness_rtnrt_id',
        'material_name',
        'price_estimate',
        'type',
        'current_status',
        'tanggal_target',
        'status'
    ];

    protected $appends = [
        'rtnrt_status',
        'last_number_status',
        'prognosa',
        'total_progress',
        'nilai_po',
    ];

    public function event_readiness_rtnrt()
    {
        return $this->belongsTo(EventReadinessRtnrt::class);
    }

    public function rekomendasi_material_rtnrt()
    {
        return $this->hasOne(RekomendasiMaterialRtnrt::class);
    }
    public function notif_material_rtnrt()
    {
        return $this->hasOne(NotifMaterialRtnrt::class);
    }
    public function job_plan_material_rtnrt()
    {
        return $this->hasOne(JobPlanMaterialRtnrt::class);
    }
    public function pr_material_rtnrt()
    {
        return $this->hasOne(PrMaterialRtnrt::class);
    }
    public function tender_material_rtnrt()
    {
        return $this->hasOne(TenderMaterialRtnrt::class);
    }
    public function po_material_rtnrt()
    {
        return $this->hasOne(PoMaterialRtnrt::class);
    }
    public function fabrikasi_material_rtnrt()
    {
        return $this->hasOne(FabrikasiMaterialRtnrt::class);
    }
    public function delivery_material_rtnrt()
    {
        return $this->hasOne(DeliveryMaterialRtnrt::class);
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

    public function getPrognosaAttribute()
    {
        $steps = [
            'delivery_material_rtnrt'  => 'target_date',
            'fabrikasi_material_rtnrt' => 'target_date',
            'po_material_rtnrt'        => 'delivery_date',
            'tender_material_rtnrt'    => 'target_date',
        ];

        foreach ($steps as $relation => $field) {

            if ($this->$relation && !empty($this->$relation->$field)) {

                $date = Carbon::parse($this->$relation->$field);

                if (empty($this->target_date)) {
                    return [
                        'date' => $date->toDateString(),
                        'color' => null,
                        'tanggal_rtnrt' => null
                    ];
                }


                $rtnrtDate = Carbon::parse($this->target_date);

                if ($this->status == 0) {
                    return [
                        'date' => null,
                        'color' => 'blue',
                        'tanggal_rtnrt' => $rtnrtDate->toDateString(),
                    ];
                }

                if ($date->gt($rtnrtDate)) {
                    $color = 'red';
                } elseif ($date->eq($rtnrtDate)) {
                    $color = 'yellow';
                } else {
                    $color = 'green';
                }

                return [
                    'date' => $date->toDateString(),
                    'color' => $color,
                    'tanggal_rtnrt' => $rtnrtDate->toDateString(),
                ];
            }
        }

        return null; // Jika tidak ada langkah yang memiliki tanggal, kembalikan null
    }

    public function getLastNumberStatusAttribute()
    {
        $steps = [
            'po_material_rtnrt'      => 'no_po',
            'pr_material_rtnrt'      => 'no_pr',
            'job_plan_material_rtnrt'=> 'no_wo',
            'notif_material_rtnrt'   => 'no_notif',
        ];

        foreach ($steps as $relation => $field) {
            if ($this->$relation && !empty($this->$relation->$field)) {
                // output: "PO (12345)"
                $stepName = strtoupper(str_replace('_material_rtnrt', '', $relation));
                return $stepName . " " . $this->$relation->$field;
            }
        }

        return null; // Jika tidak ada langkah yang memiliki tanggal, kembalikan null
    }

    public function getNilaiPoAttribute()
    {
        if (empty($this->po_material_rtnrt?->contract_new_id) || $this->po_material_rtnrt?->contract_new_id === null) {
            return $this->price_estimate;
        }

        return $this->po_material_rtnrt?->contract_new?->contract_price ?? $this->price_estimate;
    }

    public function getTotalProgressAttribute()
    {
        // urutan step dan modelnya
        $steps = [
            'rekomendasi_material_rtnrt',
            'notif_material_rtnrt',
            'job_plan_material_rtnrt',
            'pr_material_rtnrt',
            'tender_material_rtnrt',
            'po_material_rtnrt',
            'fabrikasi_material_rtnrt',
            'delivery_material_rtnrt',
        ];

        $lastStep = null;
        $lastStatus = null;

        $this->loadMissing([
            'rekomendasi_material_rtnrt',
            'notif_material_rtnrt',
            'job_plan_material_rtnrt',
            'pr_material_rtnrt',
            'tender_material_rtnrt',
            'po_material_rtnrt',
            'fabrikasi_material_rtnrt',
            'delivery_material_rtnrt',
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
                'rekomendasi_material_rtnrt' => ['rekomendasi_file' => 'readiness_rtnrt/material/rekomendasi'],
                'po_material_rtnrt'          => ['po_file' => 'readiness_rtnrt/material/po'],
                'job_plan_material_rtnrt'    => [
                    'boq_file'  => 'readiness_rtnrt/material/job_plan/boq',
                    'kak_file'  => 'readiness_rtnrt/material/job_plan/kak',
                ],
                'delivery_material_rtnrt'    => ['delivery_file' => 'readiness_rtnrt/material/delivery'],
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
