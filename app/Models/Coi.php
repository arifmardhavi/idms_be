<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Coi extends Model
{
    use HasFactory;
    protected $fillable = ["plo_id", 'tag_number_id', 'no_certificate', 'issue_date', 'overdue_date', 'coi_certificate',"coi_old_certificate" , 'rla', 'rla_issue', 'rla_overdue', 'rla_certificate', 'rla_old_certificate'];

    public function tag_number()
    {
        return $this->belongsTo(Tag_number::class);
    }

    public function plo()
    {
        return $this->belongsTo(Plo::class);
    }
}
