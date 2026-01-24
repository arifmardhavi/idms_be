<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TerminReceiptNew extends Model
{
    use HasFactory;
    protected $fillable = [
        'termin_new_id',
        'receipt_nominal',
        'receipt_file',
    ];
    public function terminNew()
    {
        return $this->belongsTo(TerminNew::class);
    }
}
