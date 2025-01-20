<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Coi extends Model
{
    use HasFactory;
    protected $fillable = ['tag_number_id', 'no_certificate', 'coi_certificate', 'issue_date', 'overdue_date', 'rla', 'rla_issue', 'rla_overdue', 'file_rla'];

    public function tag_number()
    {
        return $this->belongsTo(Tag_number::class);
    }
}
