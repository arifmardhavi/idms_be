<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contract extends Model
{
    use HasFactory;
    protected $fillable = ['no_vendor', 'vendor_name', 'no_contract', 'contract_name', 'contract_type', 'contract_date', 'contract_price', 'contract_file', 'kom', 'contract_start_date', 'contract_end_date', 'meeting_notes', 'contract_status'];

    public function termin()
    {
        return $this->hasMany(Termin::class);
    }

    public function spk()
    {
        return $this->hasMany(Spk::class);
    }

}
