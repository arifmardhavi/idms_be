<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Spk extends Model
{
    use HasFactory;

    protected $fillable = [
        'contract_id',
        'no_spk',
        'spk_name',
        'spk_start_date',
        'spk_end_date',
        'spk_price',
        'spk_file',
        'spk_status',
        'invoice',
        'invoice_value',
        'invoice_file',
    ];

    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }
}
