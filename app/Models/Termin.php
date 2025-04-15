<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Termin extends Model
{
    use HasFactory;
    protected $fillable = ['contract_id', 'termin', 'description'];

    // Relation with contract
    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }
}
