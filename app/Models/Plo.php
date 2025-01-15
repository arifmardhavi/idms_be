<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Plo extends Model
{
    use HasFactory;
    protected $fillable = ['tag_number', 'no_certificate', 'plo_certificate', 'last_plo_certificate', 'issue_date', 'overdue_date', 'rla', 'rla_issue', 'rla_overdue', 'file_rla'];
}
