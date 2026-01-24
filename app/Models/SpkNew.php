<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SpkNew extends Model
{
    use HasFactory;

    protected $table = 'spk_news';

    protected $fillable = [
        'contract_new_id',
        'no_spk',
        'spk_name',
        'spk_start_date',
        'spk_end_date',
        'spk_price',
        'spk_file',
        'spk_status',
        'receipt_nominal',
        'receipt_file',
    ];

    public function contracttNew()
    {
        return $this->belongsTo(ContractNew::class);
    }
}
