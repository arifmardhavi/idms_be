<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TerminNew extends Model
{
    use HasFactory;
    protected $fillable = [
        'contract_new_id',
        'termin',
        'description',
        'receipt_nominal',
        'receipt_file',
    ];

    public function contractNew()
    {
        return $this->belongsTo(ContractNew::class);
    }
}
