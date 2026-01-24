<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LumpsumProgressNew extends Model
{
    use HasFactory;

    protected $fillable = [
        'contract_new_id',
        'week',
        'plan',
        'actual',
        'progress_file',
    ];

    public function contractNew()
    {
        return $this->belongsTo(ContractNew::class);
    }
}
