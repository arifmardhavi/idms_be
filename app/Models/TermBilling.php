<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TermBilling extends Model
{
    use HasFactory;
    protected $fillable = ['termin_id', 'billing_value', 'payment_document'];

    // Relation with termin
    public function termin()
    {
        return $this->belongsTo(Termin::class);
    }
}
