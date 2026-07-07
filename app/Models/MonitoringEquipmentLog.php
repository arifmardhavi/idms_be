<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MonitoringEquipmentLog extends Model
{
    use HasFactory;
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
    ];


    public function tagNumber()
    {
        return $this->belongsTo(Tag_number::class);
    }
}
