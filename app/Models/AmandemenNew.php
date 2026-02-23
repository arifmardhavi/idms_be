<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AmandemenNew extends Model
{
    use HasFactory;

    protected $fillable = [
        'contract_new_id',
        'contract_price_before_amandemen', 
        'ba_agreement_file', 
        'result_amandemen_file', 
        'principle_permit_file', 
        'amandemen_price', 
        'amandemen_end_date', 
        'amandemen_penalty', 
        'amandemen_termin',
        'contract_price_before_amandemen',
    ];

    public function contractNew()
    {
        return $this->belongsTo(ContractNew::class);
    }
}
