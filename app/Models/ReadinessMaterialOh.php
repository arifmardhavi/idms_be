<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReadinessMaterialOh extends BaseModel
{
    use HasFactory;
    protected $fillable = [
        'event_readiness_oh_id',
        'material_name',
        'price_estimate',
        'type',
        'current_status',
        'tanggal_target',
        'status'
    ];

    protected $appends = [
        'oh_status',
        'last_number_status',
        'prognosa',
        'total_progress',
        'nilai_po',
    ];

    public function event_readiness_oh()
    {
        return $this->belongsTo(EventReadinessOh::class);
    }

    public function rekomendasi_material_oh()
    {
        return $this->hasOne(RekomendasiMaterialOh::class);
    }
    public function notif_material_oh()
    {
        return $this->hasOne(NotifMaterialOh::class);
    }
    public function job_plan_material_oh()
    {
        return $this->hasOne(JobPlanMaterialOh::class);
    }
    public function pr_material_oh()
    {
        return $this->hasOne(PrMaterialOh::class);
    }
    public function tender_material_oh()
    {
        return $this->hasOne(TenderMaterialOh::class);
    }
    public function po_material_oh()
    {
        return $this->hasOne(PoMaterialOh::class);
    }
    public function fabrikasi_material_oh()
    {
        return $this->hasOne(FabrikasiMaterialOh::class);
    }
    public function delivery_material_oh()
    {
        return $this->hasOne(DeliveryMaterialOh::class);
    }

    /**
     * oh_status: hanya berdasar tanggal_target (terpisah)
     */
    public function getOhStatusAttribute()
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
        $diff = $targetDate->diffInDays($today, false);
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
            'delivery_material_oh'  => 'target_date',
            'fabrikasi_material_oh' => 'target_date',
            'po_material_oh'        => 'delivery_date',
            'tender_material_oh'    => 'target_date',
        ];

        foreach ($steps as $relation => $field) {

            if ($this->$relation && !empty($this->$relation->$field)) {

                $date = Carbon::parse($this->$relation->$field);

                if (empty($this->target_date)) {
                    return [
                        'date' => $date->toDateString(),
                        'color' => null,
                        'tanggal_oh' => null
                    ];
                }


                $ohDate = Carbon::parse($this->target_date);

                if ($this->status == 0) {
                    return [
                        'date' => null,
                        'color' => 'blue',
                        'tanggal_oh' => $ohDate->toDateString(),
                    ];
                }

                if ($date->gt($ohDate)) {
                    $color = 'red';
                } elseif ($date->eq($ohDate)) {
                    $color = 'yellow';
                } else {
                    $color = 'green';
                }

                return [
                    'date' => $date->toDateString(),
                    'color' => $color,
                    'tanggal_oh' => $ohDate->toDateString(),
                ];
            }
        }

        return null; // Jika tidak ada langkah yang memiliki tanggal, kembalikan null
    }

    public function getLastNumberStatusAttribute()
    {
        $steps = [
            'po_material_oh'      => 'no_po',
            'pr_material_oh'      => 'no_pr',
            'job_plan_material_oh'=> 'no_wo',
            'notif_material_oh'   => 'no_notif',
        ];

        foreach ($steps as $relation => $field) {
            if ($this->$relation && !empty($this->$relation->$field)) {
                // contoh output: "PO (12345)"
                $stepName = strtoupper(str_replace('_material_oh', '', $relation));
                return $stepName . " " . $this->$relation->$field;
            }
        }

        return null; // Jika tidak ada langkah yang memiliki tanggal, kembalikan null
    }

    public function getNilaiPoAttribute()
    {
        if (empty($this->po_material_oh?->contract_new_id) || $this->po_material_oh?->contract_new_id === null) {
            return $this->price_estimate;
        }

        return $this->po_material_oh?->contract_new?->contract_price ?? $this->price_estimate;
    }

    public function getTotalProgressAttribute()
    {
        // urutan step dan modelnya
        $steps = [
            'rekomendasi_material_oh',
            'notif_material_oh',
            'job_plan_material_oh',
            'pr_material_oh',
            'tender_material_oh',
            'po_material_oh',
            'fabrikasi_material_oh',
            'delivery_material_oh',
        ];

        $lastStep = null;
        $lastStatus = null;

        $this->loadMissing([
            'rekomendasi_material_oh',
            'notif_material_oh',
            'job_plan_material_oh',
            'pr_material_oh',
            'tender_material_oh',
            'po_material_oh',
            'fabrikasi_material_oh',
            'delivery_material_oh',
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
                'rekomendasi_material_oh' => ['rekomendasi_file' => 'readiness_oh/material/rekomendasi'],
                'po_material_oh'          => ['po_file' => 'readiness_oh/material/po'],
                'job_plan_material_oh'    => [
                    'boq_file'  => 'readiness_oh/material/job_plan/boq',
                    'kak_file'  => 'readiness_oh/material/job_plan/kak',
                ],
                'delivery_material_oh'    => ['delivery_file' => 'readiness_oh/material/delivery'],
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
