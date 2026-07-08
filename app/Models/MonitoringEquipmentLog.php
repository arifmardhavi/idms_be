<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MonitoringEquipmentLog extends Model
{
    use HasFactory;

    protected $table = 'monitoring_equipment_logs';

    protected $fillable = [

        'tag_number_id',

        'criticality',

        'sece',

        'status',

        'jenis_kerusakan',

        'penyebab',

        'penanganan_sementara',

        'perbaikan_permanen',

        'progress_perbaikan_permanen',

        'kendala_perbaikan',

        'estimasi_perbaikan',

        'target',

        'period_code',

        'period_start',

        'period_end',

    ];

    protected $casts = [

        'period_start' => 'date',

        'period_end' => 'date',

        'estimasi_perbaikan' => 'integer',

    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function tagNumber()
    {
        return $this->belongsTo(
            Tag_number::class,
            'tag_number_id'
        );
    }

}