<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MonitoringEquipment extends BaseModel
{
    use HasFactory;

    protected $table = 'monitoring_equipment';

    protected $fillable = [

        'tag_number_id',

        'status',

        'jenis_kerusakan',

        'penyebab',

        'penanganan_sementara',

        'perbaikan_permanen',

        'progress_perbaikan_permanen',

        'kendala_perbaikan',

        'estimasi_perbaikan',

        'target'

    ];

    protected $casts = [

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

    public function logs()
    {
        return $this->hasMany(
            MonitoringEquipmentLog::class,
            'tag_number_id',
            'tag_number_id'
        )->latest('period_code');
    }

}