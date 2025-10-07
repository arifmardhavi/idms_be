<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReadinessMaterial extends BaseModel
{
    use HasFactory;
    protected $fillable = ['event_readiness_id','material_name', 'type', 'status'];
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

    public function rekomendasi_material()
    {
        return $this->hasOne(RekomendasiMaterial::class);
    }
    public function notif_material()
    {
        return $this->hasOne(NotifMaterial::class);
    }
    public function job_plan_material()
    {
        return $this->hasOne(JobPlanMaterial::class);
    }
    public function pr_material()
    {
        return $this->hasOne(PrMaterial::class);
    }
    public function tender_material()
    {
        return $this->hasOne(TenderMaterial::class);
    }
    public function po_material()
    {
        return $this->hasOne(PoMaterial::class);
    }
    public function fabrikasi_material()
    {
        return $this->hasOne(FabrikasiMaterial::class);
    }
    public function delivery_material()
    {
        return $this->hasOne(DeliveryMaterial::class);
    }


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
            'po_material'      => 'no_po',
            'pr_material'      => 'no_pr',
            'job_plan_material'=> 'no_wo',
            'notif_material'   => 'no_notif',
        ];

        foreach ($steps as $relation => $field) {
            if ($this->$relation && !empty($this->$relation->$field)) {
                // contoh output: "PO (12345)"
                $stepName = strtoupper(str_replace('_material', '', $relation)); 
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
                'rekomendasi_material' => ['rekomendasi_file' => 'readiness_ta/material/rekomendasi'],
                'po_material'          => ['po_file' => 'readiness_ta/material/po'],
                'job_plan_material'    => [
                    'boq_file'  => 'readiness_ta/material/job_plan/boq',
                    'kak_file'  => 'readiness_ta/material/job_plan/kak',
                ],
                'delivery_material'    => ['delivery_file' => 'readiness_ta/material/delivery'],
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
        $poDate = $this->po_material?->delivery_date; // step 6
        $deliveryTarget = $this->delivery_material?->target_date; // step 8

        if (empty($poDate) || empty($deliveryTarget)) {
            return null; // belum bisa dihitung
        }

        $po = Carbon::parse($poDate);
        $delivery = Carbon::parse($deliveryTarget);

        // $daysRemaining = $delivery->diffInDays($po, false);
        $daysRemaining = $po->diffInDays($delivery, false);


        $color = null;

        // Prioritaskan tanggal_ta
        if (!empty($this->event_readiness->tanggal_ta)) {
            $taDate = Carbon::parse($this->event_readiness->tanggal_ta);
            if ($po->gt($taDate)) {
                $color = 'red';
            }
        }

        if (!$color) {
            if ($po->lt($delivery)) {
                $color = 'green'; // lebih cepat dari target
            } elseif ($po->eq($delivery)) {
                $color = 'yellow'; // pas sama target
            } else {
                $color = 'yellow'; // lebih lambat dari target
            }
        }

        return [
            'days_remaining' => $daysRemaining,
            'color' => $color,
            'delivery_date' => $po->toDateString(),
            'target_date' => $delivery->toDateString(),
            'tanggal_ta' => $this->event_readiness->tanggal_ta,
        ];
    }

    public function getTotalProgressAttribute()
    {
        // urutan step dan modelnya
        $steps = [
            'rekomendasi_material',
            'notif_material',
            'job_plan_material',
            'pr_material',
            'tender_material',
            'po_material',
            'fabrikasi_material',
            'delivery_material',
        ];

        $lastStep = null;
        $lastStatus = null;

        $this->loadMissing([
            'rekomendasi_material',
            'notif_material',
            'job_plan_material',
            'pr_material',
            'tender_material',
            'po_material',
            'fabrikasi_material',
            'delivery_material',
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
