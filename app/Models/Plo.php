<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Plo extends Model
{
    use HasFactory;
    protected $fillable = ['unit_id', 'no_certificate', 'issue_date', 'overdue_date', 'plo_certificate', 'plo_old_certificate', 'rla', 'rla_issue', 'rla_overdue', 'rla_certificate', 'rla_old_certificate'];

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }
}
