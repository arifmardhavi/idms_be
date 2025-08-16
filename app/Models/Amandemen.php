<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Amandemen extends BaseModel
{
    use HasFactory;
    protected $fillable = [
        'contract_id', 
        'ba_agreement_file', 
        'result_amandemen_file', 
        'principle_permit_file', 
        'amandemen_price', 
        'amandemen_end_date', 
        'amandemen_penalty', 
        'amandemen_termin'
    ];

    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }
}
