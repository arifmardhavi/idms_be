<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Spk_progress extends Model
{
    use HasFactory;

    protected $fillable = [
        'spk_id',
        'week',
        'actual_progress',
        'plan_progress',
        'progress_file',
    ];

    public function spk()
    {
        return $this->belongsTo(Spk::class);
    }
}
